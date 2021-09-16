<?php

namespace Api\Profile;

use Api\Errors;
use Core\Dictionary\HashLinkDictionary;
use Core\Exceptions\HashedLink\HashedLinkException;
use Core\Response\JsonResponse;
use Core\Response\ResponseAbstract;
use Core\Services\GoogleAuth\GoogleAuth;
use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Db\Model\Field\EmailField;
use Db\Model\Field\Exception\InvalidValueException;
use Db\Model\Field\PasswordFiled;
use Db\Model\Field\SecretKeyField;
use Db\Transaction;
use Db\Where;
use Engine\Request;
use Models\DepositModel;
use Models\RoleModel;
use Models\UserModel;
use Models\UserPermissionModel;
use Modules\HashLinkModule;
use Modules\LogModule;
use Modules\ProfileModule;
use Modules\SignUpModule;
use Modules\UserModule;
use Serializers\ProfileSerializer;
use Serializers\UserSerializer;

class Profile {
    public static function sendAuthCode($request) {
        /**
         * @var string $email
         */
        extract($request['params']);

        $platform = getPlatform();

        $sign_up_module = (new SignUpModule($email))
            ->setPlatform($platform)
            ->validate()
            ->signUp()
            ->sendEmail();

        $response = [
            'resend_timeout' => getFloodControlPeriod(KERNEL_CONFIG['flood_control']['sign_up'])
        ];

        $csrf_token = HashLinkModule::store($sign_up_module->getUser()->id, HashLinkDictionary::SIGN_UP_TOKEN);
        $response['csrf_token'] = $csrf_token;

        JsonResponse::ok($response);
    }

    public static function verifyAuthCode($request) {
        /**
         * @var string $csrf_token
         * @var string $code
         */
        extract($request['params']);

        if (!floodControl('verify_mobile_register_code_' . $csrf_token, KERNEL_CONFIG['flood_control']['register_mobile_code'])) {
            JsonResponse::floodControlError();
        }

        try {
            $hash_link_module = new HashLinkModule($csrf_token, HashLinkDictionary::SIGN_UP_TOKEN);
            $hash_link_module->checkHash();
            $user_id = $hash_link_module->getUserId();
        } catch (HashedLinkException $e) {
            JsonResponse::errorMessage('incorrect_code', Errors::INCORRECT_CODE);
        } catch (\Exception $e) {
            JsonResponse::apiError();
        }

        $hash_model = HashLinkModule::validateCode($user_id, $code, HashLinkDictionary::SIGN_UP_MOBILE_TYPE);
        if (!$hash_model) {
            JsonResponse::errorMessage('incorrect_code', Errors::INCORRECT_CODE);
        }

        $user = UserModel::get($hash_model->user_id);
        if ($user->is2FaEnabled() && !isset($request['ga_checked'])) {
            JsonResponse::ok([
                'need_ga_code' => true
            ]);
        } else {
            $hash_model->delete(true);
            JsonResponse::ok([], [
                ResponseAbstract::AUTH_TOKEN_HEADER => ProfileModule::generateToken($user, Request::getApplicationId())
            ]);
        }
    }

    public static function verifyMobileCode($request) {
        /**
         * @var string $csrf_token
         * @var string $code
         * @var string $type
         */
        extract($request['params']);

        if (!floodControl('verify_mobile_register_code_' . ipAddress(), KERNEL_CONFIG['flood_control']['register_mobile_code'])) {
            JsonResponse::floodControlError();
        }

        if (Request::isMobileApplication()) {
            try {
                [$token_type, $code_type, $should_generate_type] = HashLinkModule::getTokenType($type);
                $hash_link_module = new HashLinkModule(
                    $csrf_token, $token_type);
                $hash_link_module->checkHash();
            } catch (HashedLinkException $e) {
                JsonResponse::errorMessage('incorrect_code', Errors::INCORRECT_CODE);
            } catch (\Exception $e) {
                JsonResponse::apiError();
            }

            $hash_model = HashLinkModule::validateCode(null, $code, $code_type);
            if (!$hash_model) {
                JsonResponse::errorMessage('incorrect_code', Errors::INCORRECT_CODE);
            }

            $hash = HashLinkModule::store($hash_model->user_id, $should_generate_type);
            $hash_model->delete(true);
            JsonResponse::ok(['hash' => $hash]);
        } else {
            JsonResponse::apiError();
        }
    }

    public static function retrieve($request) {
        $user = getUser($request);
        /* @var RoleModel $role */
        $verification = $user->verification;
        $has_deposits = !DepositModel::select(Where::equal('user_id', $user->id))->isEmpty();
        $res = [
            'user' => ProfileSerializer::detail($user),
            'roles' => $user->rolesAsArray(),
            'verification' => UserModel::USER_VERIFICATION_MAP[$verification],
            'has_notifications' => (bool) $user->notifications,
            'has_secret_key' => $user->isSecretEnabled(),
            'has_deposits' => $has_deposits,
            'ga_enabled' => $user->is2FaEnabled(),
            'is_exchange_enabled' => in_array($user->id, [ID_AGOGLEV, ID_DBORODIN, ID_NRADIONOV, ID_UGHAIRAT]),
            'can_edit_documentation' => $user->hasPermission(UserPermissionModel::DOCS_EDITOR_PERMISSION),
            'is_withdraw_disabled' => (bool) UserModule::isWithdrawDisabled($user),
        ];

        JsonResponse::ok($res);
    }

    public static function settingsRetrieve($request) {
        $user = getUser($request);

        JsonResponse::ok([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'login' => $user->login,
            'phone_number' => maskPhoneNumber($user->phone_code, $user->phone_number),
            'has_secret_key' => $user->isSecretEnabled(),
            'email' => maskEmail($user->email),
            'logs' => LogModule::getAuthLogs($user->id),
        ]);
    }

    public static function changeInfo($request) {
        /**
         * @var string $first_name First name
         * @var string $last_name Last name
         */
        $user = getUser($request);

        extract($request['params']);

        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->save();

        JsonResponse::ok('ok');
    }

    public static function changeLogin($request) {
        /**
         * @var string $login Login
         */
        $user = getUser($request);

        extract($request['params']);

        if ($user->login != $login) {
            if (!preg_match('/^[a-zA-Z0-9\_]+$/', $login)) {
                JsonResponse::errorMessage('login_must_not_have_symbols');
            }
            if (strlen($login) <= 4) {
                JsonResponse::errorMessage('login_must_be_least_characters');
            }
            $check_login = UserModel::select(Where::equal('login', $login));
            if (!$check_login->isEmpty()) {
                JsonResponse::errorMessage('login_already_used');
            }
        }

        $user->login = $login;
        $user->save();

        JsonResponse::ok('ok');
    }

    public static function checkLogin($request) {
        /**
         * @var string $login Login
         */
        extract($request['params']);

        $is_exist = !UserModel::select(Where::equal('login', $login))->isEmpty();
        if (!$is_exist) {
            JsonResponse::errorMessage('login_not_found');
        }

        JsonResponse::ok($is_exist);
    }

    public static function uploadPhoto($request) {
        $user = getUser($request);

        if (!isset($_FILES['file']) || !$_FILES['file']['tmp_name']) {
            JsonResponse::errorMessage('File is empty', 'param', false);
        }

        if (substr(mime_content_type($_FILES['file']['tmp_name']), 0, 5) !== 'image') {
            JsonResponse::errorMessage('Bad file type', 'param', false);
        }

        $storage = new \Google\Cloud\Storage\StorageClient([
            'projectId' => 'narfex-com'
        ]);
        $bucket = $storage->bucket('narfex');

        try {
            $object = $bucket->upload(
                fopen($_FILES['file']['tmp_name'], 'r'),
                [
                    'predefinedAcl' => 'publicRead'
                ]
            );

            $user->img = $object->gcsUri();
            $user->save();

            $wrapped = UserSerializer::detail($user);

            JsonResponse::ok($wrapped['photo_url']);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('Can\'t upload photo', 'param', false);
        }
    }

    public static function changeEmail($request) {
        /**
         * @var string $email
         */
        extract($request['params']);
        $user = getUser($request);

        if (!floodControl('change_email_' . $user->id, KERNEL_CONFIG['flood_control']['change_email'])) {
            JsonResponse::floodControlError();
        }

        try {
            EmailField::init()->value($email);
        } catch (InvalidValueException $e) {
            JsonResponse::errorMessage('module_please_enter_new_email', Errors::EMAIL_INCORRECT);
        }

        $user = UserModel::first(
            Where::and()
                ->set(Where::equal('email', $email))
                ->set(Where::equal('platform', PLATFORM_FINDIRI))
        );

        if ($user) {
            JsonResponse::errorMessage('module_email_already_used', Errors::EMAIL_INCORRECT);
        }

        if (Request::isMobileApplication()) {
            $token = rand(111111, 999999);
            $code = HashLinkModule::store($user->id, HashLinkDictionary::CHANGE_EMAIL_MOBILE_TYPE, 3600, $token, ['email' => $email]);
            MailAdapter::send($email, lang('main_email_change_title'), Templates::CHANGE_EMAIL_CODE, [
                'title' => lang('main_email_change_notify_title'),
                'code' => substr($code, 0, 3) . ' ' . substr($code, 3, 6),
            ]);
        } elseif (Request::isWebApplication()) {
            $hash = HashLinkModule::store($user->id, HashLinkDictionary::CHANGE_EMAIL_TYPE, 3600, null, ['email' => $email]);

            MailAdapter::send($email, lang('main_email_change_title'), Templates::CHANGE_EMAIL, [
                'link' => KERNEL_CONFIG['host'] . '/change_email?hash=' . $hash,
            ]);
        } else {
            JsonResponse::apiError();
        }

        JsonResponse::ok(['resend_timeout' => getFloodControlPeriod(KERNEL_CONFIG['flood_control']['change_email'])]);
    }

    public static function confirmEmail($request) {
        /**
         * @var string $hash
         */
        extract($request['params']);
        $user = Request::getUser();
        if (Request::isMobileApplication()) {
            $hash_model = HashLinkModule::validateCode($user->id, $hash, HashLinkDictionary::CHANGE_EMAIL_MOBILE_TYPE);
            if (!$hash_model || $hash_model->user_id !== $user->id) {
                JsonResponse::errorMessage('incorrect_code', Errors::INCORRECT_CODE);
            }

            $email = $hash_model->getExtra()->email;
        } elseif (Request::isWebApplication()) {
            try {
                $hash_module = new HashLinkModule($hash, HashLinkDictionary::CHANGE_EMAIL_TYPE, HashLinkModule::STRICT_CHECK);
                $hash_module->checkHash($user);
                $hash_model = $hash_module->getModel();
                $email = $hash_model->getExtra()->email;
            } catch (HashedLinkException $e) {
                JsonResponse::errorMessage('module_incorrect_reg_access_token', Errors::INCORRECT_CODE);
            } catch (\Exception $e) {
                JsonResponse::apiError();
            }
        } else {
            JsonResponse::apiError();
        }

        $user = UserModel::first(
            Where::and()
                ->set(Where::equal('email', $email))
                ->set(Where::equal('platform', PLATFORM_FINDIRI))
        );
        if ($user) {
            JsonResponse::apiError();
        }

        Transaction::wrap(function () use ($user, $hash_model, $email) {
            $user->email = $email;
            $user->save();
            $hash_model->delete(true);
        });

        JsonResponse::ok([
            'email' => maskEmail($email)
        ]);
    }

    public static function initGoogleCode($request) {
        $user = getUser($request);

        if ($user->is2FaEnabled()) {
            JsonResponse::errorMessage('2fa_already_enabled');
        }

        $ga_hash = ProfileModule::gaInit($user);
        $ga = new GoogleAuth();

        JsonResponse::ok(
            [
                'hash' => $ga_hash,
                'url' => $ga->getQRCodeGoogleUrl(getDomain(), $ga_hash)
            ]
        );
    }

    public static function saveGoogleCode($request) {
        /**
         * @var string $ga_code
         */
        extract($request['params']);

        $user = getUser($request);

        if ($user->is2FaEnabled()) {
            JsonResponse::errorMessage('2fa_already_enabled');
        }

        if (!ProfileModule::checkGoogleAuthCode($ga_code, $user)) {
            JsonResponse::errorMessage('api_google_code_incorrect', Errors::GA_INCORRECT);
        }

        $user->is_2fa_enabled = 1;

        $token = Transaction::wrap(function() use ($user){
            $user->save();
            UserModule::removeAccessTokens($user);
            return ProfileModule::generateToken($user, Request::getApplicationId());
        });

        JsonResponse::ok([], [
            ResponseAbstract::AUTH_TOKEN_HEADER => $token
        ]);
    }

    public static function saveSecretKey($request) {
        /* @var string $secret
         * @var string $login
         * @var string $password
         */
        extract($request['params']);

        $user = getUser($request);
        if ($user === null) {
            try {
                $user = ProfileModule::checkAuth($login, $password);
            } catch (\Exception $e) {
                JsonResponse::errorMessage($e->getMessage(), Errors::FATAL, false);
            }
        }

        if ($user->isSecretEnabled()) {
            JsonResponse::errorMessage('api_secret_key_exist_error');
        }

        $user->secret = SecretKeyField::init()->fill($secret);
        $user->save();

        JsonResponse::ok();
    }

    public static function resetGA($request) {
        /**
         * @var string $login User login
         * @var string $password User password
         * @var string $secret
         */
        extract($request['params']);

        $password_hash = PasswordFiled::init()->fill($password);

        $user = UserModel::select(Where::and()
            ->set(Where::or()->set('login', Where::OperatorEq, $login)->set('email', Where::OperatorEq, $login))
            ->set(Where::equal('platform', PLATFORM_FINDIRI))
        );
        if ($user->isEmpty() || $password_hash !== $user->first()->password) {
            JsonResponse::errorMessage('api_auth_login_or_password_incorrect');
        }

        $user = $user->first();
        /* @var UserModel $user */

        if ($user->secret !== SecretKeyField::init()->fill($secret)) {
            JsonResponse::errorMessage('api_err_secret_incorrect');
        }

        $user->disable2fa();

        JsonResponse::ok();
    }

    public static function logout($request) {
        $user = getUser($request);
        UserModule::removeAccessTokens($user, Request::getApplicationId());
        JsonResponse::ok();
    }
}
