<?php


namespace Tests;


use Blockchain\Exception\CallListenerMethodException;
use Db\Model\Exception\UndefinedValueException;
use Db\Where;
use Exceptions\WithdrawalRequests\EnoughMoneyTransferException;
use Exceptions\WithdrawalRequests\InvalidWithdrawalStatusException;
use Models\TransactionModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Models\WithdrawalRequest;
use Modules\BlockchainWithdrawalModule;
use Modules\WalletModule;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase {
    use ResetDatabase;

    public function testSendTransaction() {
        $transaction_amount = 1;
        $wallet_amount = 100;
        $limits = KERNEL_CONFIG['wallet']['withdraw_limits'][CURRENCY_BTC];
        $fee = $limits['fee'];
        $user = Seeds::createUser();
        WalletModule::generateWallets($user->id, true);
        $wallet = WalletModule::getWallet($user->id, CURRENCY_BTC);
        $wallet->addAmount($wallet_amount);
        [$withdrawal_request, $wallet] = WalletModule::transactionSend($user, 'sfihsptuwqytqwejrqerpoyi1241j', $wallet, $transaction_amount);

        /** @var UserBalanceHistoryModel $history_item */
        $history_item = UserBalanceHistoryModel::first(
            Where::and()
                ->set(Where::equal('operation', UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST))
                ->set(Where::equal('from_user_id', $user->id))
                ->set(Where::equal('object_id', $withdrawal_request->id))
        );

        /** @var WalletModel $wallet */
        $wallet = WalletModel::get($wallet->id);
        $this->assertEquals($wallet_amount - ($transaction_amount + $fee), $wallet->amount);
        $this->assertEquals(UserBalanceHistoryModel::TYPE_WALLET, $history_item->from_type);
        $this->assertEquals($wallet->id, $history_item->from_id);
        $this->assertEquals($user->id, $history_item->from_user_id);
        $this->assertEquals($wallet->currency, $history_item->from_currency);

        return [$user, $wallet, $withdrawal_request, $history_item];
    }

    /**
     * @depends testSendTransaction
     * @param array $items
     * @throws CallListenerMethodException
     * @throws EnoughMoneyTransferException
     * @throws InvalidWithdrawalStatusException
     * @throws UndefinedValueException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     */
}
