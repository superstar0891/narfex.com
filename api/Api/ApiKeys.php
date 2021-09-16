<?php

namespace Api\ApiKeys;

use Api\Errors;
use Core\Response\JsonResponse;
use Db\Model\Field\RandomTokenField;
use Db\Where;
use Models\AppTokenModel;
use Serializers\ApiKeysSerializer;
use Serializers\ErrorSerializer;

class ApiKeys {
    public static function retrieve($request) {
        $user = getUser($request);

        $keys = AppTokenModel::select(Where::and()
            ->set('owner_id', Where::OperatorEq, $user->id)
            ->set('type', Where::OperatorEq, 'exchange')
        )->map('Serializers\ApiKeysSerializer::listItem');

        JsonResponse::ok(compact('keys'));
    }

    public static function createKey($request) {
        /* @var string $name
         * @var string $allow_ips
         * @var string $permission_trading
         * @var string $permission_withdraw
         */
        extract($request['params']);

        $user = getUser($request);

        $allow_ips_prepared = array_filter(array_map('trim', explode(',', $allow_ips)));
        $allow_ips_prepared = implode(',', $allow_ips_prepared);

        $permissions = [];
        if ($permission_trading == 1) {
            $permissions[] = 'trading';
        }
        if ($permission_withdraw == 1) {
            $permissions[] = 'withdraw';
        }
        $permissions = implode(',', $permissions);

        $key = new AppTokenModel();
        $key->type = AppTokenModel::TYPE_EXCHANGE;
        $key->app_id = 8;
        $key->token = bin2hex(openssl_random_pseudo_bytes(64));
        $key->public_key = RandomTokenField::init()->fill();
        $key->owner_id = $user->id;
        $key->ip = ipAddress();
        $key->permissions = $permissions;
        $key->allow_ips = $allow_ips_prepared;
        $key->name = $name;
        $key->add_date = time();
        $key->save();

        JsonResponse::ok([
            'key' => ApiKeysSerializer::listItem($key),
            'secret' => $key->token,
        ]);
    }

    public static function deleteKey($request) {
        /* @var string $key_id */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\AppTokenModel $key */
            $key = AppTokenModel::get($key_id);
        } catch (\Exception $e) {
            JsonResponse::pageNotFoundError();
        }

        if ($key->type !== AppTokenModel::TYPE_EXCHANGE || $key->owner_id != $user->id) {
            JsonResponse::accessDeniedError();
        }

        $key->delete();

        JsonResponse::ok();
    }

    public static function editKey($request) {
        /* @var int $key_id
         * @var string $name
         * @var string $allow_ips
         * @var string $permission_trading
         * @var string $permission_withdraw
         */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\AppTokenModel $key */
            $key = AppTokenModel::get($key_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('not_found');
        }

        if ($key->type !== AppTokenModel::TYPE_EXCHANGE || $key->owner_id != $user->id) {
            JsonResponse::errorMessage('access_denied');
        }

        $allow_ips_prepared = array_filter(array_map('trim', explode(',', $allow_ips)));
        $allow_ips_prepared = implode(',', $allow_ips_prepared);

        $permissions = [];
        if ($permission_trading == 1) {
            $permissions[] = 'trading';
        }
        if ($permission_withdraw == 1) {
            $permissions[] = 'withdraw';
        }
        $permissions = implode(',', $permissions);

        $key->name = $name;
        $key->allow_ips = $allow_ips_prepared;
        $key->permissions = $permissions;
        $key->save();

        JsonResponse::ok();
    }

    public static function secretKeyRetrieve($request) {
        /* @var string $key_id */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\AppTokenModel $key */
            $key = AppTokenModel::get($key_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('not_found');
        }

        if ($key->type !== AppTokenModel::TYPE_EXCHANGE || $key->owner_id != $user->id) {
            JsonResponse::errorMessage('access_denied');
        }

        JsonResponse::ok($key->token);
    }
}
