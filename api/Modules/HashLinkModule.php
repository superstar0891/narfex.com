<?php


namespace Modules;

use Core\Dictionary\HashLinkDictionary;
use Core\Exceptions\HashedLink\HashedLinkException;
use Db\Model\Exception\ModelNotFoundException;
use Db\Where;
use Models\HashLinkModel;
use Models\UserModel;

class HashLinkModule {

    const STRICT_CHECK = true;
    const LOOSE_CHECK = false;
    const SALT = 'qo3j1l3m57';
    /**
     * @var HashLinkModel $hashModel
     */
    private $hashModel;

    /**
     * @var int $user_id
     */
    private $user_id;

    /**
     * @var string $hash
     */
    private $hash;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var bool $strict
     */
    private $strict;

    /**
     * Create new HashLinkModule instance.
     * @param string $hash
     * @param string $type
     * @param bool $strict
     * @param bool $mobile_code
     * @throws HashedLinkException
     */
    public function __construct(string $hash, string $type, bool $strict = self::LOOSE_CHECK, bool $mobile_code = false) {
        $this->hash = $hash;
        $this->strict = $strict;
        $this->type = $type;

        $hashExploded = explode('.', $hash);
        if (!isset($hashExploded[1])) {
            throw new HashedLinkException("Can not explode the hash");
        }
        $this->user_id = static::decodeUserId($hashExploded[1]);
        $this->hashModel = $strict ? $this->getLinkModelByStrict() : $this->getLinkModelByNotStrict();
    }

    public static function validateCode(?int $user_id, $code, $type) {
        $where = Where::and()
            ->set('hash', Where::OperatorEq, $code)
            ->set('type', Where::OperatorEq, $type);

        if ($user_id) {
            $where->set(Where::equal('user_id', $user_id));
        }

        $hashes = HashLinkModel::select($where);
        if ($hashes->isEmpty()) {
            return false;
        }

        $hash = $hashes->first();
        /** @var HashLinkModel $hash */

        if ($hash->expired_at < time()) {
            return false;
        }

        return $hash;
    }

    /**
     * Returns true if hash is correct
     *
     * @param UserModel|null $user
     * @return bool
     * @throws HashedLinkException
     */
    public function checkHash(UserModel $user = null): bool {
        if ($this->user_id !== $this->hashModel->user_id) {
            throw new HashedLinkException('Hashed link does not belongs to the user with id ' . $user->id);
        }

        if ($this->hashModel->expired_at < time()) {
            throw new HashedLinkException('Token expired');
        }

        return true;
    }

    /**
     * Returns user id
     *
     * @return int
     */
    public function getUserId() :int {
        return $this->user_id;
    }

    /**
     * Delete the link from database
     *
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidDeleteQueryException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public function delete() :void {
        $hashes = HashLinkModel::select(
            Where::and()
                ->set('user_id', Where::OperatorEq, $this->getUserId())
                ->set('type', Where::OperatorEq, $this->type)
        );

        foreach ($hashes as $hash) {
            /**
             * @var HashLinkModel $hash
             */
            $hash->delete(true);
        }
    }

    /**
     * Store new hash link to the database.
     *
     * @param int $user_id
     * @param string $type
     * @param int $time
     * @param string|null $token
     * @param array $extra
     * @return string
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public static function store(int $user_id, string $type, int $time = 1800, ?string $token = null, ?array $extra = null): string {
        $hash = $token ?? static::generateToken($user_id);
        $newHashedLink = new HashLinkModel();
        $newHashedLink->hash = $hash;
        $newHashedLink->user_id = $user_id;
        $newHashedLink->type = $type;
        $newHashedLink->extra = json_encode($extra);
        $newHashedLink->expired_at = time() + $time; // 1800 is 30 minutes
        $newHashedLink->save();
        return $hash;
    }

    private function getLinkModelByStrict(): HashLinkModel {
        try {
            /**
             * @var HashLinkModel $model
             */
            $model = HashLinkModel::select(
                Where::and()
                    ->set('user_id', Where::OperatorEq, $this->user_id)
                    ->set('type', Where::OperatorEq, $this->type)
            )->last();

            if ($model->hash !== $this->hash) {
                throw new HashedLinkException('Invalid hash');
            }
            return $model;
        } catch (ModelNotFoundException $e) {
            throw new HashedLinkException('Link not found (strict)');
        }
    }

    private function getLinkModelByNotStrict(): HashLinkModel {
        try {
            /**
             * @var HashLinkModel $model
             */
            $model = HashLinkModel::select(
                Where::and()
                    ->set('hash', Where::OperatorEq, $this->hash)
                    ->set('type', Where::OperatorEq, $this->type)
            )->first();

            return $model;
        } catch (ModelNotFoundException $exception) {
            throw new HashedLinkException('Hashed link not found');
        }
    }

    /**
     * @param int $id
     * @return string
     */
    private static function encodeUserId(int $id): string {
        return dechex($id);
    }

    /**
     * @param string $hex
     * @return int|null
     */
    private static function decodeUserId(string $hex): ?int {
        return hexdec($hex);
    }

    private static function generateToken(int $user_id): string {
        return getRandomString(30) . '.' . static::encodeUserId($user_id);
    }

    public function getModel() {
        return $this->hashModel;
    }

    /**
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public static function getTokenType(string $type): array {
        switch ($type) {
            case 'sign_up':
                return [HashLinkDictionary::SIGN_UP_TOKEN, HashLinkDictionary::SIGN_UP_MOBILE_TYPE, HashLinkDictionary::SIGN_UP_TYPE];
                break;
            case 'reset_password':
                return [HashLinkDictionary::RESET_PASSWORD_TOKEN, HashLinkDictionary::RESET_PASSWORD_MOBILE_TYPE, HashLinkDictionary::RESET_PASSWORD_TYPE];
                break;
            default:
                throw new \Exception();
        }
    }
}
