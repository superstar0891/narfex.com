<?php

use Db\Transaction;
use Db\Where;
use Models\BannedUserModel;
use Models\UserModel;
use Phinx\Migration\AbstractMigration;

class MigrateBannedUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
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
        Transaction::wrap(function(){
            $banned_users = UserModel::select(Where::and()->set('deleted_at', Where::OperatorIsNot, null), false);

            foreach ($banned_users as $user) {
                /** @var UserModel $user */
                $banned_user = new BannedUserModel();
                $banned_user->reason = 'old ban';
                $banned_user->user_id = $user->id;
                $banned_user->created_at_timestamp = $user->deleted_at;
                $banned_user->save();

                $user->ban_id = $banned_user->id;
                $user->save();
                $user->restore();
            }
        });
    }
}
