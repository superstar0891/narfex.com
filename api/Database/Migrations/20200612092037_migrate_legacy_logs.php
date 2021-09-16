<?php

use Phinx\Migration\AbstractMigration;

class MigrateLegacyLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
//        \Db\Transaction::wrap(function () {
//            $logs = \Models\LogModel::select();
//            foreach ($logs as $log) {
//                /** @var  \Models\LogModel $log */
//                $new_log = new \Models\UserLogModel();
//                $new_log->action = $log->action;
//                $created_at = strtotime($log->created_at);
//                if ($created_at === false) {
//                    continue;
//                }
//                $new_log->created_at_timestamp = $created_at;
//                $new_log->updated_at_timestamp = $created_at;
//                $new_log->extra = (new \Models\Logs\LegacyLog([
//                    'helper' => \Models\Logs\LegacyLog::class,
//                    'ip' => $log->ip,
//                    'browser' => $log->browser,
//                    'device' => $log->device,
//                    ]))
//                    ->toJson();
//                $new_log->admin = false;
//                $new_log->user_id = $log->user_id;
//                $new_log->save();
//            }
//        });
    }
}
