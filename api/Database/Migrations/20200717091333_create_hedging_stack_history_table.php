<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CreateHedgingStackHistoryTable extends AbstractMigration
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
        $this->table('hedging_stack_history')
            ->addColumn('stack_id', 'integer', [
                'signed' => false,
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR
            ])
            ->addColumn('type', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('amount','decimal', [
                'null' => false,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('sale_rate', 'decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('fiat_to_usd', 'decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('sale_currency', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('long_rate', 'decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('long_fee', 'decimal', [
                'null' => true,
                'precision' => '12',
                'scale' => '4',
            ])
            ->addColumn('swap_rate', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_REGULAR,
            ])
            ->addColumn('swap_fee', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_REGULAR,
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'created_at_timestamp',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'updated_at_timestamp',
            ])
            ->addIndex('stack_id')
            ->create();
    }
}
