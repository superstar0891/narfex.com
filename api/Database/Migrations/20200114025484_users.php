<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class Users extends AbstractMigration
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
        if ($this->hasTable('users')) {
            return;
        }
        $this->table('users', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'COMPACT',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('first_name', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('last_name', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'first_name',
            ])
            ->addColumn('img', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'last_name',
            ])
            ->addColumn('description', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'img',
            ])
            ->addColumn('login', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'description',
            ])
            ->addColumn('email', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'login',
            ])
            ->addColumn('password', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'email',
            ])
            ->addColumn('login_hash', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'password',
            ])
            ->addColumn('mail_hash', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'login_hash',
            ])
            ->addColumn('ga_hash', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'mail_hash',
            ])
            ->addColumn('secret', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ga_hash',
            ])
            ->addColumn('phone_code', 'string', [
                'null' => true,
                'limit' => 8,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'secret',
            ])
            ->addColumn('phone_number', 'string', [
                'null' => true,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'phone_code',
            ])
            ->addColumn('phone_verified', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'phone_number',
            ])
            ->addColumn('refer', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'phone_verified',
            ])
            ->addColumn('role', 'integer', [
                'null' => true,
                'default' => '0',
                'limit' => '6',
                'after' => 'refer',
            ])
            ->addColumn('ip', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'role',
            ])
            ->addColumn('join_date', 'date', [
                'null' => true,
                'after' => 'ip',
            ])
            ->addColumn('agent_date', 'date', [
                'null' => true,
                'after' => 'join_date',
            ])
            ->addColumn('group_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'agent_date',
            ])
            ->addColumn('agent_percent', 'float', [
                'null' => true,
                'after' => 'group_id',
            ])
            ->addColumn('verification', 'integer', [
                'null' => true,
                'limit' => '1',
                'after' => 'agent_percent',
            ])
            ->addColumn('pool_terms', 'integer', [
                'null' => true,
                'limit' => '1',
                'after' => 'verification',
            ])
            ->addColumn('notifications', 'integer', [
                'null' => true,
                'limit' => '1',
                'after' => 'pool_terms',
            ])
            ->addColumn('active', 'integer', [
                'null' => true,
                'default' => '0',
                'limit' => '1',
                'after' => 'notifications',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'active',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => '_delete',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at_timestamp',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'updated_at_timestamp',
            ])
            ->addColumn('representative_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'deleted_at',
            ])
            ->addColumn('need_reset_password', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'representative_id',
            ])
            ->addColumn('invite_link_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'need_reset_password',
            ])
            ->addIndex(['email'], [
                'name' => 'email_2',
                'unique' => true,
            ])
            ->addIndex(['mail_hash'], [
                'name' => 'reg_hash',
                'unique' => true,
            ])
            ->addIndex(['login_hash'], [
                'name' => 'login_hash',
                'unique' => false,
            ])
            ->addIndex(['refer'], [
                'name' => 'refer',
                'unique' => false,
            ])
            ->addIndex(['login'], [
                'name' => 'login',
                'unique' => false,
            ])
            ->addIndex(['first_name', 'last_name'], [
                'name' => 'first_last_name',
                'unique' => false,
                'type' => 'fulltext',
            ])
            ->create();

    }
}
