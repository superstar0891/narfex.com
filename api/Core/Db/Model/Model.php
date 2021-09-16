<?php

namespace Db\Model;

use Db\Db;
use Db\Exception as DbException;
use Db\Model\Exception\FieldNotFoundException;
use Db\Model\Exception\ModelNotFoundException;
use Db\Model\Exception\ModelUndefinedFieldsException;
use Db\Model\Exception\TableNameUndefinedException;
use Db\Model\Exception\UndefinedValueException;
use Db\Model\Field\AutofillField;
use Db\Model\Field\CreatedAtField;
use Db\Model\Field\DeletedAtField;
use Db\Model\Field\Exception\InvalidValueException;
use Db\Model\Field\UpdatedAtField;
use Db\QueryBuilder;
use Db\Transaction;
use Db\Where;

/* @property int id
 * @property int created_at_timestamp
 * @property int updated_at_timestamp
 * @property int deleted_at
*/
abstract class Model {
    abstract protected static function fields(): array;

    protected static $table_name;

    protected static $fields = [];

    protected static $has_default_fields = true;
    
    private $process_delete = false;

    /**
     * @return mixed
     * @throws TableNameUndefinedException
     */
    public static function getTableName() {
        if (!static::$table_name) {
            throw new TableNameUndefinedException();
        }

        return static::$table_name;
    }

    /**
     * @return bool
     */
    public static function hasDefaultFields(): bool {
        return static::$has_default_fields;
    }

    public function toJson(): array {
        return [
            'id' => (int) $this->id,
        ];
    }

    /**
     * @throws ModelUndefinedFieldsException
     */
    protected static function initFields() {
        if (isset(static::$fields[static::class]) && static::$fields[static::class]=== null) {
            throw new ModelUndefinedFieldsException();
        }

        if (empty(static::$fields[static::class])) {
            static::$fields[static::class] = static::fields();

            if (static::$has_default_fields) {
                static::$fields[static::class] = array_merge(static::$fields[static::class], [
                    'created_at_timestamp' => Field\CreatedAtField::init(),
                    'updated_at_timestamp' => Field\UpdatedAtField::init(),
                    'deleted_at' => Field\DeletedAtField::init(),
                ]);
            }
        }
    }

    /**
     * @return array
     * @throws ModelUndefinedFieldsException
     */
    public static function getFields(): array {
        static::initFields();
        return static::$fields[static::class];
    }

    protected $id;

    protected $values = [];

    protected $should_update_fields = [];

    function __construct() {
        static::initFields();
        $this->setDefaultFields();
        $this->setAutofillFields();
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws FieldNotFoundException
     * @throws ModelUndefinedFieldsException
     */
    function __get(string $name) {
        if ($name == 'id') {
            return $this->id;
        }

        if (!isset(static::getFields()[$name])) {
            throw new FieldNotFoundException($name);
        }

        return $this->values[$name];
    }

    /**
     * @param string $name
     * @param $value
     *
     * @throws FieldNotFoundException
     * @throws ModelUndefinedFieldsException
     * @throws InvalidValueException
     */
    function __set(string $name, $value) {
        $fields = static::getFields();

        if (!isset($fields[$name])) {
            throw new FieldNotFoundException();
        }

        try {
            $clean_value = $fields[$name]->value($value);
        } catch (InvalidValueException $e) {
            throw new InvalidValueException("Attempt to write an invalid value in a field: $name");
        }

        if (!isset($this->values[$name]) || $this->values[$name] != $clean_value) {
            $this->values[$name] = $clean_value;
            $this->should_update_fields[] = $name;
        }
    }

    /**
     * @return QueryBuilder
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     */
    public static function queryBuilder() {
        $model_columns = array_keys(static::getFields());
        $model_columns[] = 'id';

        $builder = QueryBuilder::query(static::getTableName(), static::class);
        $builder->columns($model_columns);

        return $builder;
    }

    /**
     * @param Where|null $where
     * @param bool $exclude_deleted
     *
     * @return ModelSet
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidSelectQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws TableNameUndefinedException
     */
    public static function select(Where $where = null, $exclude_deleted = true): ModelSet {
        if (static::hasDefaultFields()) {
            if ($exclude_deleted) {
                if ($where) {
                    $where = Where::and()->set($where)->set('deleted_at', Where::OperatorIs, null);
                } else {
                    $where = Where::and()->set('deleted_at', Where::OperatorIs, null);
                }
            }
        }

        $rows = Db::select(static::getTableName(), null, $where);

        return self::rowsToSet($rows);
    }

    public static function first(Where $where = null, $exclude_deleted = true): ?self {
        $model_set = static::select($where, $exclude_deleted);
        if ($model_set->isEmpty()) {
            return null;
        }

        return $model_set->first();
    }


    /**
     * @param int $id
     * @param bool $exclude_deleted
     *
     * @return Model
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidSelectQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelNotFoundException
     * @throws TableNameUndefinedException
     */
    public static function get(int $id, $exclude_deleted = true): self {
        $w = Where::and()->set('id', Where::OperatorEq, $id);
        if (static::hasDefaultFields() && $exclude_deleted) {
            $w->set('deleted_at', Where::OperatorIs, null);
        }

        $row = Db::get(static::getTableName(), null, $w);
        if (!$row) {
            throw new ModelNotFoundException();
        }

        $item = new static();
        $item->id = $id;
        $item->setValues($row);

        return $item;
    }

    /**
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidInsertQueryException
     * @throws DbException\InvalidUpdateQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     * @throws UndefinedValueException
     */
    public function save() {
        if ($this->id) {
            Transaction::wrap(function () {
                if (!$this->process_delete) {
                    $this->onUpdate();
                }
                // Update value of UpdatedAt field
                $this->updateUpdatedAtField();

                // Get all field => values
                $assoc_values = $this->getValues();

                // Filter fields which should update
                $should_update_field_names = $this->should_update_fields;
                if (empty($should_update_field_names)) {
                    return;
                }

                $should_update_assoc = array_filter(
                    $assoc_values,
                    function ($key) use ($should_update_field_names) {
                        return in_array($key, $should_update_field_names);
                    },
                    ARRAY_FILTER_USE_KEY
                );

                if (empty($should_update_assoc)) {
                    return;
                }

                // Update model
                Db::update(static::getTableName(), $should_update_assoc, Where::equal('id', $this->id));

                // Reset fields
                $this->should_update_fields = [];
            });
        } else {
            // Set values of CreatedAt and UpdatedAt fields
            if (is_null($this->created_at_timestamp)) {
                $this->setCreatedAtField();
            }
            $this->updateUpdatedAtField();

            Transaction::wrap(function () {
                // Get model values
                $assoc_values = $this->getValues();
                $fields = array_keys($assoc_values);
                $values = array_values($assoc_values);
                $id = Db::insert(static::getTableName(), $fields, $values);
                $this->id = $id;

                $this->onCreate();
            });

        }
    }

    /**
     * @param bool $force_hard
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidDeleteQueryException
     * @throws DbException\InvalidInsertQueryException
     * @throws DbException\InvalidUpdateQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     * @throws UndefinedValueException
     */
    public function delete($force_hard = false) {
        if ($this->id) {
            Transaction::wrap(function () use($force_hard) {
                $this->process_delete = true;
                $this->onDelete();
                if ($force_hard || KERNEL_CONFIG['model']['delete'] == 'hard') {
                    $this->hardDelete();
                } else if (KERNEL_CONFIG['model']['delete'] == 'soft') {
                    $this->softDelete();
                }
                $this->process_delete = false;
            });

        }
    }

    /**
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidInsertQueryException
     * @throws DbException\InvalidUpdateQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     * @throws UndefinedValueException
     */
    public function restore() {
        if ($this->id) {
            $this->clearDeletedAtField();
            $this->save();
        }
    }

    /**
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidInsertQueryException
     * @throws DbException\InvalidUpdateQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     * @throws UndefinedValueException
     */
    public function softDelete() {
        if ($this->id) {
            $this->updateDeletedAtField();
            $this->save();
        }
    }

    /**
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidDeleteQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws TableNameUndefinedException
     */
    public function hardDelete() {
        if ($this->id) {
            Db::delete(static::getTableName(), Where::equal('id', $this->id));
        }
    }

    /**
     * @param array $rows
     *
     * @return ModelSet
     */
    public static function rowsToSet(array $rows): ModelSet {
        $items = [];
        foreach ($rows as $row) {
            $item = new static();
            $item->id = (int) $row['id'];
            $item->setValues($row);

            $items[] = $item;
        }

        return new ModelSet($items);
    }

    /**
     * @param array $values
     *
     * @throws ModelUndefinedFieldsException
     * @throws UndefinedValueException
     */
    private function setValues(array $values) {
        foreach (static::getFields() as $field_name => $field) {
            if (isset($values[$field_name])) {
                $this->values[$field_name] = $field->value($values[$field_name]);
            } elseif (($default = $field->getDefault()) !== null) {
                $this->values[$field_name] = $default;
            } elseif ($default = $field->isNull()) {
                $this->values[$field_name] = null;
            } else {
                throw new UndefinedValueException('Field name: ' . $field_name);
            }
        }
    }

    /**
     * @return array
     * @throws ModelUndefinedFieldsException
     * @throws UndefinedValueException
     */
    private function getValues(): array {
        $fields = [];
        $values = [];
        foreach (static::getFields() as $field_name => $field) {
            $fields[] = $field_name;

            if (isset($this->values[$field_name])) {
                $values[] = $this->values[$field_name];
            } elseif (($default = $field->getDefault()) !== null) {
                $values[] = $default;
            } elseif ($field->isNull()) {
                $values[] = null;
            } else {
                throw new UndefinedValueException('Field name: ' . $field_name);
            }
        }

        return array_combine($fields, $values);
    }

    /**
     * Initializes autofilled fields
     * It has no effect on CreatedAt, UpdatedAt, DeletedAt fields
     */
    private function setAutofillFields() {
        foreach (static::getFields() as $field_name => $field) {
            if ($field instanceof AutofillField) {
                $this->values[$field_name] = $field->fill();
            }
        }
    }

    /**
     * Sets CreatedAt field
     */
    private function setCreatedAtField() {
        foreach (static::getFields() as $field_name => $field) {
            if ($field instanceof CreatedAtField) {
                $this->$field_name = time();
            }
        }
    }

    /**
     * Sets or updates UpdatedAt field
     */
    private function updateUpdatedAtField() {
        foreach (static::getFields() as $field_name => $field) {
            if ($field instanceof UpdatedAtField) {
                $this->$field_name = time();
            }
        }
    }

    /**
     * Updates DeletedAt field
     */
    private function updateDeletedAtField() {
        foreach (static::getFields() as $field_name => $field) {
            if ($field instanceof DeletedAtField && $this->values[$field_name] === null) {
                $this->$field_name = time();
            }
        }
    }

    /**
     * Clear DeletedAt field
     */
    private function clearDeletedAtField() {
        foreach (static::getFields() as $field_name => $field) {
            if ($field instanceof DeletedAtField && $this->values[$field_name] !== null) {
                $this->$field_name = null;
            }
        }
    }

    public static function lockTableWrite() {
        Db::lockTableWrite(self::$table_name);
    }

    public function setId(int $id) {
        $this->id = $id;
    }

    private function setDefaultFields() :void {
        foreach (static::getFields() as $field_name => $field) {
            if (($default = $field->getDefault()) !== null) {
                $this->values[$field_name] = $default;
            } elseif ($default = $field->isNull()) {
                $this->values[$field_name] = null;
            }
        }
    }

    protected function onUpdate() {
        return;
    }

    protected function onCreate() {
        return;
    }

    protected function onDelete() {
        return;
    }

    public function existsInDatabase() {
        return (bool) $this->id;
    }
}
