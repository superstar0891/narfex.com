<?php

use Db\Transaction;
use Db\Where;
use Models\UserModel;
use Phinx\Migration\AbstractMigration;

class ChangeUserGaHashes extends AbstractMigration
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
        $users = UserModel::select(Where::and()->set('ga_hash', Where::OperatorIsNot, NULL));

        Transaction::wrap(function () use ($users) {
            foreach ($users as $user) {
                /** @var UserModel $user */
                $user->setGaHash($user->ga_hash);
            }
        });
    }
}
