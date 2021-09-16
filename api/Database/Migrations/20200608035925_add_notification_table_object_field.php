<?php

use Db\Where;
use Models\NotificationModel;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class AddNotificationTableObjectField extends AbstractMigration
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
        $this->table('notifications')
            ->addColumn('object_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'important'
            ])
            ->update();

        $notifications = NotificationModel::queryBuilder()
            ->where(Where::and()
                ->set('type', Where::OperatorIN, ['withdrawal', 'withdrawal_reject', 'transaction_receive', 'refill'])
            )->select();
        $notifications = NotificationModel::rowsToSet($notifications);
        foreach ($notifications as $notification) {
            /** @var NotificationModel $notification */
            $notification->delete(true);
        }
    }
}
