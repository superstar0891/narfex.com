<?php

use Phinx\Migration\AbstractMigration;

class Deposits extends AbstractMigration
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
        if ($this->hasTable('deposits')) {
            return;
        }
        $this->table('deposits', [
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
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'after' => 'id',
            ])
            ->addColumn('operation', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'operation',
            ])
            ->addColumn('currency', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'amount',
            ])
            ->addColumn('days', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '5',
                'after' => 'currency',
            ])
            ->addColumn('wallet_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'after' => 'days',
            ])
            ->addColumn('date_start', 'date', [
                'null' => false,
                'after' => 'wallet_id',
            ])
            ->addColumn('plan', 'integer', [
                'null' => false,
                'limit' => '2',
                'after' => 'date_start',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'plan',
            ])
            ->addColumn('charge_code', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'status',
            ])
            ->addColumn('charge_id', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'charge_code',
            ])
            ->addColumn('charged_amount', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'charge_id',
            ])
            ->addColumn('manual', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'charged_amount',
            ])
            ->addColumn('manual_comment', 'string', [
                'null' => true,
                'limit' => 2048,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'manual',
            ])
            ->addColumn('dynamic_percent', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'manual_comment',
            ])
            ->addColumn('dynamic_coeff', 'float', [
                'null' => false,
                'default' => '1',
                'after' => 'dynamic_percent',
            ])
            ->addColumn('dynamic_profit', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'dynamic_coeff',
            ])
            ->addColumn('dynamic_profit_share', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'dynamic_profit',
            ])
            ->addColumn('dynamic_curr_percent', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'dynamic_profit_share',
            ])
            ->addColumn('dynamic_daily_percent', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'dynamic_curr_percent',
            ])
            ->addColumn('dynamic_start_percent', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'dynamic_daily_percent',
            ])
            ->addColumn('withdraw_disabled', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'dynamic_start_percent',
            ])
            ->addColumn('withdraw_disabled_coeff', 'float', [
                'null' => false,
                'default' => '1',
                'after' => 'withdraw_disabled',
            ])
            ->addColumn('plan_percent_coeff', 'float', [
                'null' => false,
                'default' => '1',
                'after' => 'withdraw_disabled_coeff',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'plan_percent_coeff',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'after' => '_delete',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at',
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
            ->create();

    }
}
