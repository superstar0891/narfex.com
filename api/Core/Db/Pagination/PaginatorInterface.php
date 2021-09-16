<?php

namespace Db\Pagination;

use Db\Model\ModelSet;

interface PaginatorInterface {

    public function toArray(): array;

    public function getItems(): ModelSet;

    public function getLimit(): int;

    public function getNext(): ?int;

    public function isOnLastPage(): bool;
}
