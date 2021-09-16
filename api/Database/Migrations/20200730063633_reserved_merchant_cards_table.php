<?php

use Phinx\Migration\AbstractMigration;

class ReservedMerchantCardsTable extends AbstractMigration
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
        $this->table('reserved_merchant_cards')
            ->addColumn('card_id','integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('user_id','integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('operation', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('amount', 'decimal', [
                'null' => false,
                'precision' => '16',
                'scale' => '2',
            ])
            ->addColumn('got_amount', 'decimal', [
                'null' => true,
                'precision' => '16',
                'scale' => '2',
            ])
            ->addColumn('fee', 'decimal', [
                'null' => false,
                'precision' => '16',
                'scale' => '4',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('hash', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('wallet_address', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('card_number', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('card_owner_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('promo_code', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('initial_rate', 'decimal', [
                'null' => false,
                'precision' => '16',
                'scale' => '2',
            ])
            ->addColumn('current_rate', 'decimal', [
                'null' => false,
                'precision' => '16',
                'scale' => '2',
            ])
            ->addColumn('rate_update_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('validate', 'integer', [
                'null' => false,
                'limit' => '1',
                'default' => 0,
                'signed' => false,
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'ip',
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
            ->addIndex(['card_id'])
            ->addIndex(['operation'])
            ->addIndex(['operation', 'status'])
            ->create();
    }
}
