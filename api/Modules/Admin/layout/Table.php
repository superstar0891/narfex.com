<?php

namespace Admin\layout;

use Admin\helpers\LayoutBuilder;
use Serializers\AdminSerializer;

class Table extends Layout implements DataManagementInterface {

    const TABLE_PER_PAGE = 50;

    private $headers = [];
    private $rows = [];
    private $total_count;
    private $search = null;
    /* @var Action */
    private $search_action = null;

    private $action_params = [];
    private $per_page = self::TABLE_PER_PAGE;

    /* @var Action $action */
    private $action;

    public static function withHeaders(array $headers): Table {
        $inst = new Table();
        $inst->setHeaders($headers);
        return $inst;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
    }

    public function setRows(array $rows) {
        $this->rows = $rows;
    }

    public function setTotalCount(int $total_count) {
        $this->total_count = $total_count;
    }

    public function setParams(array $params) {
        $this->action_params = $params;
    }

    public function setPerPage(int $count) {
        $this->per_page = $count;
    }

    public function setAction(Action $action) {
        $this->action = $action;
    }

    public function setSearch(?array $search, Action $action): self {
        $this->search = $search;
        $this->search_action = $action;
        return $this;
    }

    public function getId(): string {
        return md5(serialize($this->headers));
    }

    public function serialize(array $items = []): array {
        $builder = new LayoutBuilder;

        /* @var Layout $header */
        foreach ($this->headers as $header) {
            if (!($header instanceof TableColumn)) {
                if (!is_array($header)) {
                    $header = [$header];
                }
                $header = TableColumn::withParams(...$header);
            }
            $builder->push($header);
        }


        return AdminSerializer::table(
            $this->getId(),
            AdminSerializer::tableHeaderRow(
                ...$builder->build()
            ),
            $items,
            [
                'total_count' => $this->total_count,
                'paging' => $this->paging(),
                'search' => $this->search,
                'search_action' => $this->search ? $this->search_action->serialize() : null,
            ]
        );
    }

    private function paging() {
        $items = [];
        $pages = $this->total_count > 0 ? floor($this->total_count / $this->per_page) : 0;
        if ($this->total_count === $this->per_page) {
            $pages = 0;
        }
        $cur_page = isset($this->action_params['page']) ? $this->action_params['page'] : 0;

        if ($cur_page > 0) {
            $items[] = AdminSerializer::pagingItem('Prev', [
                'action' => $this->action->use()
                    ->setParams(array_merge($this->action_params, ['page' => $cur_page - 1]))
                    ->serialize(),
            ]);
        }

        $items[] = AdminSerializer::pagingItem($cur_page + 1);

        if ($cur_page < $pages) {
            $items[] = AdminSerializer::pagingItem('Next', [
                'action' => $this->action->use()
                    ->setParams(array_merge($this->action_params, ['page' => $cur_page + 1]))
                    ->serialize(),
            ]);
        }

        return AdminSerializer::paging($items);
    }
}
