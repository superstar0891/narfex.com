<?php

namespace Admin\modules;

use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\NumberFormat;
use Db\Model\ModelSet;
use Db\Where;
use Models\FiatInvoiceModel;
use Models\UserModel;

class FiatInvoices extends PageContainer {

    /* @var \Admin\helpers\DataManager */
    private $table;

    public function registerActions() {
        $this->table = $this
            ->createManagedTable(FiatInvoiceModel::class, ['ID', 'User', 'Amount', 'Date'])
            ->setDataMapper(function (ModelSet $invoices) {
                $user_ids = array_filter($invoices->column('user_id'));
                $users = UserModel::select(Where::in('id', $user_ids));
                return $invoices->map(function (FiatInvoiceModel $invoice) use ($users) {
                    /* @var UserModel $user */
                    $user = $users->getItem($invoice->user_id);
                    return [
                        $invoice->id,
                        $user->fullName(),
                        NumberFormat::withParams($invoice->amount, $invoice->currency),
                        date('d/m/Y', $invoice->created_at_timestamp),
                    ];
                });
            });
    }

    public function build() {
        $this->layout->push(Block::withParams('Invoices', $this->table->build()));
    }
}
