<?php

namespace Db\Pagination;

use Db\Model\ModelSet;
use Serializers\PagingSerializer;

class Paginator extends AbstractPaginator implements PaginatorInterface {
    /**
     * @var int $total
     */
    private $total;

    public function __construct(ModelSet $items, int $start_from, int $limit, ?int $total) {
        $this->items = $items;
        $this->start_from = $start_from === 0 ? 0 : $start_from;
        $this->limit = $limit;
        $this->total = $total;
    }

    public function toArray(): array {
        return [
            'items' => $this->items->toArray(),
            'count' => $this->getLimit(),
            'total' => $this->getTotal(),
            'next' => $this->getNext()
        ];
    }

    public function getItems(): ModelSet {
        return $this->items;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function getOffset(): int {
        return $this->getStartfrom() * $this->getLimit();
    }

    public function getStartfrom(): int {
        return $this->start_from;
    }

    public function getNext(): ?int {
        if ($this->items->isEmpty() || $this->isOnLastPage()) return null;

        return $this->getStartfrom() + 1;
    }

    private function totalPages() {
        if (!$this->getTotal()) return 0;
        return ceil($this->getTotal() / $this->getLimit());
    }

    public function isOnLastPage(): bool {
        return $this->getStartfrom() >= $this->totalPages() - 1;
    }

    public function getTotal(): ?int {
        return $this->total;
    }

    public static function getOffsetByCountAndLimit(int $limit, int $page): int {
        return $page * $limit;
    }
}
