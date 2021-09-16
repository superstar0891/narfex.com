<?php

use Phinx\Migration\AbstractMigration;

class CreateHedgingStackTable extends AbstractMigration
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
        $this->table('hedging_stack')
            ->addColumn('name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('primary_currency', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('primary_amount','decimal', [
                'null' => false,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('secondary_currency','string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('buy_rate','decimal', [
                'null' => false,
                'precision' => '22',
                'scale' => '2',
            ])
            ->addColumn('fiat_to_usd', 'decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '8',
            ])
            ->addColumn('short_rate','decimal', [
                'null' => false,
                'precision' => '22',
                'scale' => '2',
            ])
            ->addColumn('buy_fee','decimal', [
                'null' => false,
                'precision' => '12',
                'scale' => '4',
            ])
            ->addColumn('short_fee','decimal', [
                'null' => false,
                'precision' => '12',
                'scale' => '4',
            ])
            ->addColumn('close_short_fee','decimal', [
                'null' => true,
                'precision' => '12',
                'scale' => '4',
            ])
            ->addColumn('close_short_rate','decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '2',
            ])
            ->addColumn('close_short_currency','string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('close_long_fee','decimal', [
                'null' => true,
                'precision' => '12',
                'scale' => '4',
            ])
            ->addColumn('close_long_rate','decimal', [
                'null' => true,
                'precision' => '22',
                'scale' => '2',
            ])
            ->addColumn('close_long_currency','string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
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
            ->create();
    }
}
