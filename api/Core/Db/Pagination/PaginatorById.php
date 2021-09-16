<?php

namespace Db\Pagination;

use Db\Model\ModelSet;

class PaginatorById extends AbstractPaginator implements PaginatorInterface{

    public function __construct(ModelSet $items, ?int $start_from, int $limit) {
        $this->items = $items;
        $this->start_from = $start_from;
        $this->limit = $limit;
    }

    public function toArray(): array {
        return [
            'items' => $this->items->toArray(),
            'count' => $this->getLimit(),
            'next' => $this->getNext()
        ];
    }

    public function getItems(): ModelSet {
        return $this->items;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function getNext(): ?int {
        if ($this->items->isEmpty() || $this->isOnLastPage() || is_null($this->start_from)) return null;
        return $this->items->last()->id;
    }

    public function isOnLastPage(): bool {
        if ($this->items->count() < $this->limit) {
            return true;
        }

        return false;
    }
}
