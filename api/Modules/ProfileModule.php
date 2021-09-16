<?php

namespace Modules;

use Core\Dictionary\HashLinkDictionary;
use Core\Services\GoogleAuth\GoogleAuth;
use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Db\Model\Field\PasswordFiled;
use Db\Where;
use Exception;
use Models\AppTokenModel;
use Models\Logs\UserAuthorizeLog;
use Models\PermissionModel;
use Models\RoleModel;
use Models\UserModel;

class ProfileModule {
    /**
     * @var array Permissions cache
     */
    private static $permissions_tmp = [];

    /**
     * Signs up new user
     *
     * @param string $first_name User first name
     * @param string $last_name  User last name
     *
     * @return int
     * @throws Exception
     */
    public static function signUp(string $first_name, string $last_name): int {
        // sign up

        return 1;
    }

    /**
     * Checks if user has selected permissions
     *
     * @param int    $user_id
     * @param int    $user_role_id
     * @param string ...$permissions
     *
     * @return bool
     * @throws Exception
     */
    public static function hasPermission(int $user_id, int $user_role_id, string ...$permissions): bool {
        if (array_key_exists($user_id, static::$permissions_tmp)) {
            // Get user permissions from cache
            $user_permissions_names = static::$permissions_tmp[$user_id];
        } else {
            $user_role = RoleModel::get($user_role_id);
            $user_permission_ids = explode(',', $user_role->permissions);
            $user_permissions = PermissionModel::select(Where::in('id', $user_permission_ids));
            $user_permissions_names = $user_permissions->column('name');

            // Cache permissions
            static::$permissions_tmp[$user_id] = $user_permissions_names;
        }

        foreach ($permissions as $permission) {
            if (!in_array($permission, $user_permissions_names)) {
                return false;
            }
        }

        return true;
    }

    public static function generateToken(UserModel $user, $app_id, $type = AppTokenModel::TYPE_USER): string {
        UserModule::removeAccessTokens($user, $app_id);

        $token = new AppTokenModel();
        $token->app_id = $app_id;
        $token->token = bin2hex(openssl_random_pseudo_bytes(64));
        $token->owner_id = $user->id;
        $token->type = AppTokenModel::TYPE_USER;
        $token->ip = ipAddress();
        $token->save();

        UserLogModule::addLog(UserAuthorizeLog::USER_AUTHORIZE_ACTION, new UserAuthorizeLog([]), false, $user);
        NotificationsModule::sendUserAuthorizeNotification($user);

        return $token->token;
    }

    /* @param \Models\UserModel $user
     * @return string
     * @throws Exception
     */
    public static function gaInit(UserModel $user): string {
        if (!trim($user->ga_hash) || is_null($user->ga_hash)) {
            $ga = new GoogleAuth;
            $ga_hash = $ga->createSecret();
            $user->setGaHash($ga_hash);
        } else {
            $ga_hash = $user->getGaDecoded();
        }

        return $ga_hash;
    }

    public static function checkAuth(string $login, string $password): UserModel {
        $password_hash = PasswordFiled::init()->fill($password);

        /** @var UserModel|null $user */
        $user = UserModel::first(
            Where::and()
                ->set(Where::or()->set('login', Where::OperatorEq, $login)->set('email', Where::OperatorEq, $login))
                ->set(Where::equal('platform', PLATFORM_FINDIRI)),
            false
        );

        if (!$user) {
            throw new Exception(lang('api_auth_login_or_password_incorrect'));
        }

        if ($user->isBanned()) {
            throw new Exception(lang('api_auth_user_banned'));
        }

        if ($user->need_reset_password) {
            throw new Exception(lang('require_reset_password'));
        }

        if ($password_hash !== $user->password) {
            throw new Exception(lang('api_auth_login_or_password_incorrect'));
        }

        return $user;
    }

    public static function checkGoogleAuthCode(?string $ga_code, UserModel $user): bool {
        $ga_hash = $user->getGaDecoded();
        return checkGoogleAuth($ga_code, $ga_hash);
    }

    public static function resetPasswordMobileApp(UserModel $user) {
        $code = rand(111111, 999999);
        HashLinkModule::store($user->id, HashLinkDictionary::RESET_PASSWORD_MOBILE_TYPE, 3600, $code);
        MailAdapter::send($user->email, lang('mail_restore_password'), Templates::RESET_PASSWORD_CODE, [
            'code' => substr($code, 0, 3) . ' ' . substr($code, 3, 6),
        ]);
    }

    public static function resetPasswordWebsite(UserModel $user) {
        $hash = HashLinkModule::store($user->id, HashLinkDictionary::RESET_PASSWORD_TYPE);

        MailAdapter::send($user->email, lang('mail_restore_password'), Templates::RESET_PASSWORD, [
            'link' => KERNEL_CONFIG['host'] . '/reset_password?hash=' . $hash,
        ]);
    }
}
