<?php

namespace Db\Model;

use Db\Db;
use Db\Model\Exception\ModelNotFoundException;
use Db\Where;
use Iterator;

class ModelSet implements Iterator {
    private $position = 0;

    private $items = [];
    private $items_map = [];

    /**
     * ModelSet constructor.
     *
     * @param array $items
     */
    function __construct(array $items = []) {
        $this->position = 0;
        $this->items = $items;

        /* @var Model $item */
        foreach ($items as $item) {
            $this->items_map[$item->id] = $item;
        }
    }

    /**
     * Iterator interface method
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * Iterator interface method
     */
    public function current() {
        $keys = array_keys($this->items);
        $key = $keys[$this->position];
        return $this->items[$key];
    }

    /**
     * Iterator interface method
     */
    public function key() {
        $keys = array_keys($this->items);
        return $keys[$this->position];
    }

    /**
     * Iterator interface method
     */
    public function next() {
        ++$this->position;
    }

    /**
     * Iterator interface method
     */
    public function valid() {
        return $this->position < count($this->items);
    }

    /**
     * @return Model
     * @throws ModelNotFoundException
     */
    public function first() {
        if (empty($this->items)) {
            throw new ModelNotFoundException();
        }

        return reset($this->items);
    }

    /**
     * @return Model
     * @throws ModelNotFoundException
     */
    public function last(): Model {
        if (empty($this->items)) {
            throw new ModelNotFoundException();
        }

        return $this->items[$this->count() - 1];
    }

    /**
     * @param string $column_name
     *
     * @return array
     */
    public function column(string $column_name): array {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->$column_name;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool {
        return empty($this->items);
    }

    /**
     * @return int
     */
    public function count(): int {
        return count($this->items);
    }

    /**
     * @param string $serializer
     *
     * @return array
     */
    public function serialize(string $serializer): array {
        $serialized_result = [];
        foreach ($this as $item) {
            $serialized_result[] = $serializer($item);
        }

        return $serialized_result;
    }

    /**
     * @param callable $callback
     *
     * @return array
     */
    public function map(callable $callback): array {
        $array_result = [];
        foreach ($this as $item) {
            if ($res = $callback($item))
            $array_result[] = $res;
        }

        return $array_result;
    }

    public function toJson(): array {
        $array_result = [];
        foreach ($this as $key => $item) {
            $array_result[] = $item->toJson();
        }

        return $array_result;
    }

    public function push(Model $model) {
        $this->items[] = $model;
        $this->items_map[$model->id] = $model;
    }

    public function filter(callable $comparator): ModelSet {
        return new ModelSet(array_filter($this->items, $comparator));
    }

    public function getItem(int $id): ?Model {
        return isset($this->items_map[$id]) ? $this->items_map[$id] : null;
    }

    public function toArray(): array {
        if ($this->isEmpty()) return [];

        $array_result = [];
        foreach ($this as $item) {
            $array_result[] = $item;
        }

        return $array_result;
    }

    public function delete() {
        if ($this->isEmpty()) {
            return;
        }
        $ids = $this->column('id');
        try {
            /** @var Model $class */
            $class = get_class($this->first());
        } catch (ModelNotFoundException $e) {
            return;
        }

        if (!$class::queryBuilder()
            ->where(Where::in('id', $ids))
            ->delete()) {

            throw new \Exception('Can not delete old tokens');
        }
    }
}
