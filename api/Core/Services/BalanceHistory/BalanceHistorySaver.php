<?php


namespace Core\Services\BalanceHistory;


use Core\Exceptions\BalanceHistory\BalanceHistoryFieldValidationException;
use Models\UserBalanceHistoryModel;

class BalanceHistorySaver {
    use SetFromAndToId, SetAmounts;

    protected $from_user_id_field = 'from_user_id';
    protected $to_user_id_field = 'to_user_id';
    protected $from_currency_field = 'from_currency';
    protected $to_currency_field = 'to_currency';

    protected $from_currency = null;
    protected $from_user_id = null;

    protected $to_user_id = null;
    protected $to_currency = null;

    protected $to_type = null;
    protected $to_id = null;
    protected $from_type = null;
    protected $from_id = null;

    protected $from_amount = null;
    protected $to_amount = null;

    protected $operation = null;
    protected $object_id = null;
    protected $created_at = null;

    public static function make() {
        return new self;
    }

    public function setOperation(int $operation) {
        $this->operation = $operation;

        return $this;
    }

    public function setCreatedAt(int $created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    public function setObjectId(int $object_id) {
        $this->object_id = $object_id;
        return $this;
    }

    /**
     * @return UserBalanceHistoryModel
     * @throws BalanceHistoryFieldValidationException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public function save(): UserBalanceHistoryModel {
        $model = new UserBalanceHistoryModel();
        $model->from_user_id = $this->from_user_id;
        $model->to_user_id = $this->to_user_id;
        $model->from_type = $this->from_type;
        $model->from_id = $this->from_id;
        $model->to_type = $this->to_type;
        $model->to_id = $this->to_id;
        $model->from_amount = $this->from_amount;
        $model->to_amount = $this->to_amount;
        $model->from_currency = $this->from_currency;
        $model->to_currency = $this->to_currency;
        $model->operation = $this->operation;
        $model->object_id = $this->object_id;
        if ($this->created_at) {
            $model->created_at_timestamp = $this->created_at;
        }
        $model->save();

        return $model;
    }
}
