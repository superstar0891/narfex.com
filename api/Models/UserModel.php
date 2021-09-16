<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DateField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\EmailField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\LoginField;
use Db\Model\Field\RandomHashField;
use Db\Model\Field\TextField;
use Db\Model\Model;
use Db\Where;
use Modules\UserModule;

/**
 * @property int id
 * @property string login
 * @property string email
 * @property string password
 * @property string first_name
 * @property string last_name
 * @property string login_hash
 * @property string permissions
 * @property int role //@todo удалить когда перейдем на новые роли roles
 * @property string platform
 * @property string roles
 * @property string ga_hash
 * @property string is_2fa_enabled
 * @property string secret
 * @property int notifications
 * @property int img
 * @property string refer
 * @property string mail_hash
 * @property int active
 * @property string ip
 * @property string join_date
 * @property string agent_date
 * @property string phone_code
 * @property string phone_number
 * @property int phone_verified
 * @property int representative_id
 * @property int need_reset_password
 * @property int invite_link_id
 * @property int birthday
 * @property int|null ban_id
 * @property string city
 * @property string country
 * @property string verification_result
 * @property string applicant_id
 * @property int verification
 * @property int verification_request_at
 */
class UserModel extends Model {

    const USER_VERIFIED = 1;
    const USER_UNVERIFIED = 0;
    const USER_VERIFY_PENDING = 2;
    const USER_REJECTED = 3;
    const USER_TEMPORARY_REJECTED = 4;

    const USER_VERIFICATION_MAP = [
        UserModel::USER_VERIFIED => 'verified',
        UserModel::USER_UNVERIFIED => 'not_verified',
        UserModel::USER_VERIFY_PENDING => 'pending',
        UserModel::USER_REJECTED => 'rejected',
        UserModel::USER_TEMPORARY_REJECTED => 'temporary_rejected',
    ];

    protected static $table_name = 'users';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'login' => LoginField::init()->setNull(true),
            'email' => EmailField::init(),
            'password' => CharField::init()->setNull(true),
            'first_name' => CharField::init()->setLength(512)->setNull(true),
            'last_name' => CharField::init()->setLength(512)->setNull(true),
            'login_hash' => RandomHashField::init()->setLength(64)->setNull(true),
            'platform' => CharField::init()->setLength(100)->setDefault(PLATFORM_FINDIRI),
            'roles' => CharField::init()->setLength(256)->setNull(true),
            'permissions' => CharField::init()->setLength(256)->setNull(true),
            'role' => IdField::init()->setNull(true),
            'ga_hash' => CharField::init()->setLength(32)->setNull(true),
            'is_2fa_enabled' => IntField::init()->setLength(1)->setDefault(0),
            'phone_code' => IntField::init()->setNull(true)->setDefault(0),
            'phone_number' => IntField::init()->setNull(true)->setDefault(0),
            'verification' => IntField::init()->setDefault(0),
            'secret' => CharField::init()->setLength(128)->setNull(true),
            'notifications' => IntField::init()->setLength(1)->setNull(true),
            'img' => CharField::init()->setLength(150)->setNull(true),
            'refer' => CharField::init()->setNull(true)->setDefault(null),
            'mail_hash' => CharField::init()->setLength(32),
            'active' => IntField::init()->setDefault(0),
            'ip' => CharField::init()->setLength(32),
            'join_date' => DateTimeField::init(),
            'agent_date' => DateTimeField::init()->setNull(true),
            '_delete' => IntField::init()->setDefault(0),
            'phone_verified' => IntField::init()->setDefault(0),
            'representative_id' => IntField::init()->setDefault(0)->setNull(true),
            'need_reset_password' => IntField::init()->setDefault(0),
            'invite_link_id' => IntField::init()->setNull(true)->setDefault(null),
            'city' => CharField::init()->setNull(true)->setDefault(null),
            'country' => CharField::init()->setNull(true)->setDefault(null),
            'birthday' => DateField::init()->setNull(true),
            'verification_result' => TextField::init()->setNull(true),
            'verification_request_at' => IntField::init()->setNull(true),
            'applicant_id' => CharField::init()->setNull(true)->setDefault(null),
            'ban_id' => IdField::init()->setNull(),
            ];
    }

    public function fullName(): string {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function is2FaEnabled(): bool {
        return (bool) $this->is_2fa_enabled == 1;
    }

    public function isSecretEnabled(): bool {
        return (bool) $this->secret;
    }

    public function disable2fa() {
        $this->ga_hash = null;
        $this->is_2fa_enabled = 0;
        $this->save();
    }

    public function resetSecretKey() {
        $this->secret = null;
        $this->save();
    }

    public function getInviterId(): ?int {
        if ($this->refer) {
            $refer = explode(',', $this->refer)[0];
            return $refer !== '' ? $refer : null;
        }

        return null;
    }

    public function isWithdrawalDisabled() {
        return UserModule::isWithdrawDisabled($this);
    }

    public function isAdmin() {
        return $this->hasRole(UserRoleModel::ADMIN_ROLE);
    }

    public function isTranslator() {
        return $this->hasRole(UserRoleModel::TRANSLATOR_ROLE);
    }

    public function rolesAsArray(): array {
        return array_filter(
            array_map('trim', explode(',', $this->roles)), function ($role) {
                return boolval($role);
            }
        );
    }

    public function permissionsAsArray(): array {
        return array_filter(
            array_map('trim', explode(',', $this->permissions)), function ($permission) {
                return boolval($permission);
            }
        );
    }

    public function allPermissionsAsArray(): array {
        return $this->getPermissions();
    }

    public function hasPermissions($permissions): bool {
        if ($this->isAdmin()) {
            return true;
        }
        $filter_permissions = array_filter($permissions, function ($permission) {
            return $this->hasPermission($permission);
        });
        return count($filter_permissions) === count($permissions);
    }

    public function hasRole(string $role): bool {
        return in_array($role, $this->rolesAsArray(), true);
    }

    public function hasPermission(string $permission): bool {
        if ($this->isAdmin()) {
            return true;
        }
        $permissions = $this->getPermissions();

        return in_array($permission, $permissions, true);
    }

    public function hasRoles(array $roles): bool {
        $filter_roles = array_filter($roles, function ($role) {
            return $this->hasRole($role);
        });
        return count($filter_roles) === count($roles);
    }

    private function getPermissions() {
        static $permissions = [];

        if (!empty($permissions)) {
            return $permissions;
        }

        $permissions = $this->permissionsAsArray();

        if (!empty($roles = $this->rolesAsArray())) {
            $roles = UserRoleModel::select(Where::in('role_name', $roles));

            foreach ($roles as $role) {
                /** @var UserRoleModel $role */
                $permissions = array_merge($permissions, $role->permissionsAsArray());
            }
        }

        return array_unique($permissions);
    }

    public function removePermission(string $permission_name) {
        if (!$this->hasPermission($permission_name)) {
            return $this;
        }

        $current_permissions = $this->permissionsAsArray();
        $permissions = array_filter($current_permissions, function ($item) use ($permission_name) {
            return $item !== $permission_name;
        });
        $this->permissions = implode(',', $permissions);

        return $this;
    }

    public function removeRole(string $role_name) {
        if (!$this->hasRole($role_name)) {
            return $this;
        }

        $current_roles = $this->rolesAsArray();
        $roles = array_filter($current_roles, function ($item) use ($role_name) {
            return $item !== $role_name;
        });
        $this->roles = implode(',', $roles);

        return $this;
    }

    public function addPermission(string $permission_name) {
        if ($this->hasPermission($permission_name)) {
            return $this;
        }

        $current_permissions = $this->permissionsAsArray();
        $current_permissions[] = $permission_name;
        $this->permissions = implode(',', $current_permissions);

        return $this;
    }

    public function addRole(string $role_name) {
        if ($this->hasRole($role_name)) {
            return $this;
        }

        $current_roles = $this->rolesAsArray();
        $current_roles[] = $role_name;
        $this->roles = implode(',', $current_roles);

        return $this;
    }

    public function isBanned(): bool {
        return !is_null($this->ban_id);
    }

    public function setGaHash($raw_hash) {
        $hash = encrypt($raw_hash);
        $hex_hash = bin2hex($hash);
        $this->ga_hash = $hex_hash;
        $this->save();
    }

    public function getGaDecoded(): string {
        $hash = hex2bin($this->ga_hash);
        return decrypt($hash);
    }

    public function fromBitcoinovnet(): bool {
        return PLATFORM_BITCOINOVNET === $this->platform;
    }

    public function hasAdminAccess() {
        return $this->isAdmin() || !empty($this->allPermissionsAsArray());
    }
}
