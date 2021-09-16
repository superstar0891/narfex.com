<?php

namespace Db;

use Db\Exception\DbAdapterException;
use Db\Exception\InvalidSelectQueryException;
use Db\Exception\InvalidWhereOperatorException;
use Db\Model\ModelSet;
use Db\Pagination\AbstractPaginator;
use Db\Pagination\Paginator;
use Db\Pagination\PaginatorById;

class QueryBuilder {
    /**
     * @param string $table_name
     * @param string $model_name
     *
     * @return QueryBuilder
     */
    public static function query(string $table_name, string $model_name) {
        $inst = new self();

        $inst->table_name = $table_name;
        $inst->model_name = $model_name;

        return $inst;
    }

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var string
     */
    private $model_name;

    /**
     * @var string
     */
    private $for_update = false;

    /**
     * @var array
     */
    private $builder = [];

    /**
     * SelectBuilder constructor.
     */
    function __construct() {
        $this->builder = [
            'columns' => null,
            'where' => null,
            'group_by' => null,
            'order_by' => null,
            'limit' => null,
            'offset' => null,
        ];
    }

    /**
     * @param array $columns
     * @param bool $reset_default_columns
     *
     * @return QueryBuilder
     */
    public function columns(array $columns, $reset_default_columns = false): QueryBuilder {
        $add_columns = [];
        foreach ($columns as $column_key => $column_annotation) {
            if (is_string($column_key)) {
                $add_columns[] = "{$column_key} AS {$column_annotation}";
            } else {
                $add_columns[] = $column_annotation;
            }
        }

        if ($this->builder['columns'] === null || $reset_default_columns) {
            $this->builder['columns'] = $add_columns;
        } else {
            $this->builder['columns'] = array_merge($this->builder['columns'], $add_columns);
        }

        return $this;
    }

    /**
     * @param Where $where
     *
     * @return QueryBuilder
     */
    public function where(Where $where): QueryBuilder {
        $this->builder['where'] = $where;

        return $this;
    }

    /**
     * @param array|string $group_by
     *
     * @return QueryBuilder
     * for select only
     */
    public function groupBy($group_by): QueryBuilder {
        if (!is_array($group_by)) {
            $group_by = [$group_by];
        }

        $this->builder['group_by'] = $group_by;

        return $this;
    }

    /**
     * @param array $order_by
     *
     * @return QueryBuilder
     * for select only
     */
    public function orderBy(array $order_by): QueryBuilder {
        $this->builder['order_by'] = $order_by;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return QueryBuilder
     * for select only
     */
    public function limit(?int $limit): QueryBuilder {
        $this->builder['limit'] = $limit;

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return QueryBuilder
     * for select only
     */
    public function offset(?int $offset): QueryBuilder {
        $this->builder['offset'] = $offset;

        return $this;
    }

    /**
     * @param bool $for_update
     *
     * @return QueryBuilder
     * for get only
     */
    public function forUpdate($for_update = true) {
        $this->for_update = $for_update;

        return $this;
    }

    /**
     * @return array
     * @throws DbAdapterException
     * @throws InvalidSelectQueryException
     * @throws InvalidWhereOperatorException
     */
    public function select() {
        $table_name = $this->table_name;

        return Db::select($table_name,
                          $this->builder['columns'],
                          $this->builder['where'],
                          $this->builder['group_by'],
                          $this->builder['order_by'],
                          $this->builder['limit'],
                          $this->builder['offset'],
                          $this->for_update);
    }

    /**
     * @return array
     * @throws DbAdapterException
     * @throws InvalidSelectQueryException
     * @throws InvalidWhereOperatorException
     */
    public function get() {
        $table_name = $this->table_name;

        return Db::get($table_name, $this->builder['columns'], $this->builder['where'], $this->for_update, $this->builder['group_by']);
    }

    /**
     * @param array
     * @return bool
     * @throws DbAdapterException
     * @throws InvalidWhereOperatorException
     */
    public function update(array $updated) {
        $table_name = $this->table_name;

        return Db::update($table_name, $updated, $this->builder['where']);
    }

    public function paginateById(?int $start_from = null, ?int $limit = AbstractPaginator::DEFAULT_COUNT) {
        $query = clone $this;
        if (!is_null($start_from) && $start_from != 0) {
            /** @var Where $where */
            $where = $query->builder['where'];
            $query->where($where->set('id', Where::OperatorLower, $start_from));
        }
        $raw_rows = $query->limit($limit)
            ->orderBy(['id' => 'DESC'])
            ->select();

        /** @var ModelSet $rows */
        $rows = $this->model_name::rowsToSet($raw_rows);

        if (is_null($start_from)) {
            $start_from = $rows->isEmpty() ? null : $rows->first()->id;
        }

        return new PaginatorById($rows, $start_from, $limit);
    }

    public function paginate(int $page, ?int $limit = AbstractPaginator::DEFAULT_COUNT) {
        $cloneThis = clone $this;
        $rawRows = $this
            ->limit($limit)
            ->offset(Paginator::getOffsetByCountAndLimit($limit, $page))
            ->select();
        /**
         * @var ModelSet $rows
         */
        $rows = $this->model_name::rowsToSet($rawRows);
        $total = $cloneThis
            ->offset(null)
            ->limit(null)
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->get();
        $total = (int) !$rows->isEmpty() ? $total['cnt'] : 0;
        return new Paginator($rows, $page, $limit, $total);
    }

    public function delete() {
        if (!isset($this->builder['where']) || !$this->builder['where']) {
            throw new InvalidWhereOperatorException('Can not truncate table');
        }
        return Db::delete($this->table_name, $this->builder['where']);
    }
}
