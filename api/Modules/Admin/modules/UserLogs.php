<?php

namespace Admin\modules;

use Admin\common\UserLogCommon;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\serializers\UserLogSerializer;
use Db\Model\ModelSet;
use Db\Where;
use Models\UserLogModel;

class UserLogs extends PageContainer {
    /* @var DataManager */
    private $table;

    public function registerActions() {
        $this->table = $this
            ->createManagedTable(UserLogModel::class, ['Id', 'User ID', 'Action', 'Created', 'Info', 'Ip', 'Device', 'Browser'])
            ->setDataMapper(function (ModelSet $logs)  {
                return UserLogSerializer::rows($logs);
            })
            ->setSearchForm(function () {
                return UserLogCommon::searchForm();
            })
            ->setFiltering(function (array $filters, Where $where) {
                return UserLogCommon::dataFiltering($filters, $where);
            })
            ->setOrderBy(['created_at_timestamp' => 'DESC']);
    }

    public function build() {
        $this->layout->push(Block::withParams('Logs', $this->table->build()));
    }
}
