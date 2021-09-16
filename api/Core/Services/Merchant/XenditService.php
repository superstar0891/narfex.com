<?php
namespace Core\Services\Merchant;

use Core\Services\Redis\RedisAdapter;
use Core\Services\Telegram\SendService;
use Db\Where;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Models\UserModel;
use Models\WithdrawalModel;
use Models\XenditWalletModel;
use Xendit\Balance;
use Xendit\Disbursements;
use Xendit\Exceptions\ApiException;
use Xendit\Invoice;
use Xendit\VirtualAccounts;
use Xendit\Xendit;

class XenditService {
    const XENDIT_BANKS_REDIS_KEY = 'xendit_disbustments';
    const AVAILABLE_BANKS = ['BRI', 'MANDIRI', 'PERMATA'];

    const WITHDRAWAL_FEE = 10000;
    const WITHDRAWAL_PERCENT_FEE = 1;

    const MIN_WITHDRAWAL_AMOUNT = 100000;
    const MAX_WITHDRAWAL_AMOUNT = 100000000;

    const REFILL_FEE = 10000;
    const REFILL_PERCENT_FEE = 1;

    /**
     * @return array
     * @throws \Xendit\Exceptions\ApiException
     */
    public static function getBanks(): array {
        $redis = RedisAdapter::shared();
        if ($banks = $redis->get(self::XENDIT_BANKS_REDIS_KEY)) {
            return json_decode($banks, true);
        }

        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        $banks = Disbursements::getAvailableBanks();

        $banks = array_values(array_filter($banks, function($item){
            return in_array($item['code'], self::AVAILABLE_BANKS);
        }));
        $banks = array_map(function($item) {
            $item['limits'] = [
                'min' => null,
                'max' => 100000000
            ];
            return $item;
        }, $banks);
        $redis->set(self::XENDIT_BANKS_REDIS_KEY, json_encode($banks), 3600);
        return $banks;
    }

    public static function getInvoice($id) {
        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        return Invoice::retrieve($id);
    }

    public static function createInvoice(array $params): array {
        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        return Invoice::create($params);
    }

    public static function getVirtualPayment(string $payment_id): array {
        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        return VirtualAccounts::getFVAPayment($payment_id);
    }

    /**
     * @param string $id
     * @param string $bank_code
     * @param string $account_holder_name
     * @param string $account_number
     * @param string $description
     * @param float $amount
     * @param string $email_to
     * @return array
     * @throws XenditException
     * @throws ApiException
     */
    public static function createDisbursement(string $id, string $bank_code, string $account_holder_name, string $account_number, string $description, float $amount, ?array $email_to = null): array {
        if (!isset(KERNEL_CONFIG['merchant']['xendit']['secret_api_key'])) {
            throw new XenditException('No api key provided');
        }
        $client = new Client();
        try {
            $req = $client->post('https://api.xendit.co/disbursements', [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => null
                ],
                RequestOptions::AUTH => [KERNEL_CONFIG['merchant']['xendit']['secret_api_key'], ''],
                RequestOptions::JSON => self::getDisbursementData($id, $bank_code, $account_holder_name, $account_number, $description, $amount, $email_to)
            ]);

            return json_decode($req->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $response_body = $e->getResponse()->getBody()->getContents();

            $telegram_service = new SendService();
            $telegram_service->sendMessage('#XENDIT_API_ERROR' . PHP_EOL . $response_body);

            throw new ApiException("API ERROR: $response_body", 400, 'CLIENT_EXCEPTION');
        }
    }

    public static function createDisbursements(array $withdrawals): array {
        if (!isset(KERNEL_CONFIG['merchant']['xendit']['secret_api_key'])) {
            throw new XenditException('No api key provided');
        }
        $client = new Client();
        try {
            $req = $client->post('https://api.xendit.co/batch_disbursements', [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => null
                ],
                RequestOptions::AUTH => [KERNEL_CONFIG['merchant']['xendit']['secret_api_key'], ''],
                RequestOptions::JSON => [
                    'reference' => 'auto_withdrawals_' . time(),
                    'disbursements' => array_map(function (WithdrawalModel $withdrawal) {
                        return self::getDisbursementData(
                            $withdrawal->id,
                            $withdrawal->bank_code,
                            $withdrawal->account_holder_name,
                            $withdrawal->account_number,
                            'xendit disbursement #' . $withdrawal->id,
                            $withdrawal->amount,
                            json_decode($withdrawal->email_to, true) ?? []
                        );
                    }, $withdrawals),
                ]
            ]);

            return json_decode($req->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw new ApiException('API ERROR', 400, 'CLIENT_EXCEPTION');
        }
    }

    private static function getDisbursementData(string $id, string $bank_code, string $account_holder_name, string $account_number, string $description, float $amount, array $email_to = []) {
        return [
            'external_id' => $id,
            'bank_code' => $bank_code,
            'account_holder_name' => $account_holder_name,
            'account_number' => $account_number,
            'description' => $description,
            'amount' => $amount,
            'email_to' => $email_to
        ];
    }

    public static function checkAvailableBalance(float $amount): bool {
        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        $response = Balance::getBalance('CASH');
        $balance = (float) $response['balance'];
        return $balance > $amount;
    }

    public static function getBalance(): float {
        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);
        $response = Balance::getBalance('CASH');
        return (float) $response['balance'];
    }

    /**
     * @param UserModel $user
     * @param string $bank
     * @return XenditWalletModel
     * @throws XenditException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public static function createVirtualAccount(string $bank): XenditWalletModel {
        if (!in_array($bank, self::AVAILABLE_BANKS)) {
            throw new XenditException('The bank is not available');
        }
        $bank = strtoupper($bank);

        Xendit::setApiKey(KERNEL_CONFIG['merchant']['xendit']['secret_api_key']);

        $xendit_wallet = new XenditWalletModel();
        $xendit_wallet->user_id = null;
        $xendit_wallet->account_number = null;
        $xendit_wallet->status = 'pending';
        $xendit_wallet->bank_code = $bank;
        $xendit_wallet->expired_at = time() + (60 * 60 * 24 * 365 * 50);
        $xendit_wallet->save();

        $account = VirtualAccounts::create([
            'external_id' => (string) $xendit_wallet->id,
            'bank_code' => $bank,
            'name' => "NARFEX"
        ]);

        $xendit_wallet->account_number = $account['account_number'];
        $xendit_wallet->save();

        return $xendit_wallet;
    }

    /**
     * @param int $user_id
     * @param string $bank_code
     * @return XenditWalletModel
     * @throws XenditException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public static function assignVirtualAccount(int $user_id, string $bank_code): XenditWalletModel {
        $bank_code = strtoupper($bank_code);

        $xendit_wallets = XenditWalletModel::queryBuilder()
            ->where(
                Where::and()
                    ->set('user_id', Where::OperatorIs, null)
                    ->set('status', Where::OperatorEq, XenditWalletModel::STATUS_ACTIVE)
                    ->set('bank_code', Where::OperatorEq, $bank_code)
            )
            ->limit(1)
            ->select();

        $xendit_wallets = XenditWalletModel::rowsToSet($xendit_wallets);

        if ($xendit_wallets->isEmpty()) {
            throw new XenditException('There is no available accounts');
        }

        $xendit_wallet = $xendit_wallets->first();
        /**
         * @var XenditWalletModel $xendit_wallet
         */
        $xendit_wallet->user_id = $user_id;
        $xendit_wallet->save();

        return $xendit_wallet;
    }
}
