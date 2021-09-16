<?php

namespace Db\Pagination;

use Db\Model\ModelSet;

class AbstractPaginator implements PaginatorInterface {
    const DEFAULT_COUNT = 25;
    /**
     * @var ModelSet $items
     */
    protected $items;

    /**
     * @var int|null $start_from
     */
    protected $start_from;

    /**
     * @var int $limit
     */
    protected $limit;

    public function toArray(): array {
        return [];
    }

    public function getItems(): ModelSet {
        return $this->items;
    }

    public function getLimit(): int {
        $this->limit;
    }

    public function getNext(): ?int {
        return null;
    }

    public function isOnLastPage(): bool {
        return true;
    }
}
