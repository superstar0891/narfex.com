<?php


namespace Modules;

use Core\Dictionary\HashLinkDictionary;
use Core\Response\JsonResponse;
use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Db\Model\Field\RandomHashField;
use Db\Where;
use Engine\Request;
use Models\UserModel;

class SignUpModule {

    private $email;
    /** @var UserModel|null $user  */
    private $user = null;

    private $platform = null;

    private $app_id = null;

    public function __construct(string $email) {
        $this->email = $email;
    }

    public function validate(): self {
        /** @var UserModel $user */
        $user = UserModel::first(Where::and()
                ->set(Where::equal('email', $this->email))
                ->set(Where::equal('platform', $this->platform))
            , false);

        if ($user) {
            $this->user = $user;
        }

        if (Request::isAdminApplication()) {
            if ($user === null || !$user->hasAdminAccess()) {
                JsonResponse::accessDeniedError();
            }
        }

        if (!floodControl('registrations_' . ipAddress(), KERNEL_CONFIG['flood_control']['registrations'])) {
            JsonResponse::floodControlError();
        }

        return $this;
    }

    public function signUp(): self {
        if ($this->user !== null) {
            return $this;
        }

        $user = new UserModel();
        $user->email = $this->email;
        $user->mail_hash = RandomHashField::init()->fill();
        $user->agent_date = date('Y-m-d H:i:s');
        $user->join_date = date('Y-m-d H:i:s');
        $user->ip = ipAddress();
        $user->role = 4;
        $user->platform = $this->platform ?: PLATFORM_FINDIRI;
        $user->save();
        $this->user = $user;

        return $this;
    }

    public function sendEmail(): SignUpModule {
        if (!isset($this->user)) {
            throw new \Exception('User is not defined.');
        }

        $token = rand(111111, 999999);
        $code = HashLinkModule::store($this->user->id, HashLinkDictionary::SIGN_UP_MOBILE_TYPE, 3600, $token);

        if ($this->platform === PLATFORM_BITCOINOVNET) {
            MailAdapter::sendBitcoinovnet($this->email, lang('mail_confirm_email_address'), Templates::AUTH_BITCOINOVNET, [
                'code' => substr($code, 0, 3) . ' ' . substr($code, 3, 6),
            ]);
        } else {
            MailAdapter::send($this->email, lang('mail_confirm_email_address'), Templates::AUTH, [
                'code' => substr($code, 0, 3) . ' ' . substr($code, 3, 6),
            ]);
        }

        return $this;
    }

    public function getUser(): UserModel {
        if (!$this->user) {
            throw new \Exception('User is not registered yet');
        }

        return $this->user;
    }

    public function setPlatform(string $platform) {
        if (!in_array($platform, [PLATFORM_FINDIRI, PLATFORM_BITCOINOVNET], true)) {
            throw new \LogicException('incorrect platform');
        }

        $this->platform = $platform;
        return $this;
    }
}
