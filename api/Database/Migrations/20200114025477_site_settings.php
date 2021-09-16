<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class SiteSettings extends AbstractMigration
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
        if ($this->hasTable('site_settings')) {
            return;
        }
        $this->table('site_settings', [
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
            ->addColumn('cb_commission', 'double', [
                'null' => false,
                'after' => 'id',
            ])
            ->addColumn('site_name', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'cb_commission',
            ])
            ->addColumn('host', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'site_name',
            ])
            ->addColumn('default_language', 'string', [
                'null' => false,
                'limit' => 8,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'host',
            ])
            ->addColumn('currencies', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'default_language',
            ])
            ->addColumn('exchange_commision', 'float', [
                'null' => false,
                'default' => '0',
                'after' => 'currencies',
            ])
            ->addColumn('liquidity_amount', 'float', [
                'null' => false,
                'after' => 'exchange_commision',
            ])
            ->addColumn('liquidity_limit', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'liquidity_amount',
            ])
            ->addColumn('liquidity_count', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'liquidity_limit',
            ])
            ->addColumn('liquidity_spread', 'float', [
                'null' => false,
                'after' => 'liquidity_count',
            ])
            ->addColumn('commerce_expiration', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'liquidity_spread',
            ])
            ->addColumn('commerce_commision', 'float', [
                'null' => false,
                'after' => 'commerce_expiration',
            ])
            ->addColumn('wallet_withdraw_daily_max', 'float', [
                'null' => false,
                'after' => 'commerce_commision',
            ])
            ->addColumn('wallet_withdraw_enabled', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'wallet_withdraw_daily_max',
            ])
            ->addColumn('wallet_withdraw_daily_freq', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'wallet_withdraw_enabled',
            ])
            ->addColumn('wallet_withdraw_hour_freq', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'wallet_withdraw_daily_freq',
            ])
            ->addColumn('wallet_withdraw_email_notif', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'wallet_withdraw_hour_freq',
            ])
            ->addColumn('wallet_withdraw_emails', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'wallet_withdraw_email_notif',
            ])
            ->addColumn('wallet_withdraw_delay', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'wallet_withdraw_emails',
            ])
            ->addColumn('wallet_refill_emails', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'wallet_withdraw_delay',
            ])
            ->addColumn('deposit_fast_withdraw_threshold', 'float', [
                'null' => false,
                'after' => 'wallet_refill_emails',
            ])
            ->addColumn('deposit_profit_drop', 'float', [
                'null' => false,
                'after' => 'deposit_fast_withdraw_threshold',
            ])
            ->addColumn('deposit_withdraw_min', 'float', [
                'null' => false,
                'default' => '5',
                'after' => 'deposit_profit_drop',
            ])
            ->addColumn('dynamic_minimal_coeff', 'float', [
                'null' => false,
                'default' => '0.01',
                'after' => 'deposit_withdraw_min',
            ])
            ->addColumn('dynamic_point_x', 'float', [
                'null' => false,
                'default' => '0.2',
                'after' => 'dynamic_minimal_coeff',
            ])
            ->addColumn('dynamic_point_y', 'float', [
                'null' => false,
                'default' => '0.5',
                'after' => 'dynamic_point_x',
            ])
            ->addColumn('dynamic_start_percent', 'float', [
                'null' => false,
                'default' => '0.5',
                'after' => 'dynamic_point_y',
            ])
            ->addColumn('withdraw_disabled_coeff', 'float', [
                'null' => false,
                'default' => '1.03',
                'after' => 'dynamic_start_percent',
            ])
            ->addColumn('pool_amount', 'double', [
                'null' => true,
                'after' => 'withdraw_disabled_coeff',
            ])
            ->addColumn('pool_date', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'pool_amount',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'pool_date',
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
            ->addColumn('agent_percent', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'deleted_at',
            ])
            ->addColumn('representative_percent', 'integer', [
                'null' => false,
                'default' => '5',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'agent_percent',
            ])
            ->create();

    }
}
