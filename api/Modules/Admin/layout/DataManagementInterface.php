<?php

namespace Admin\layout;

interface DataManagementInterface {
    public function getId(): string;
    public function setTotalCount(int $total_count);
    public function setRows(array $rows);
    public function setParams(array $params);
    public function setPerPage(int $count);
    public function setAction(Action $action);
    public function setSearch(?array $search, Action $action);
}
