<?php


namespace Api\Xendit;


use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\Refill\RefillService;
use Core\Services\Telegram\SendService;
use Core\Services\Merchant\XenditException;
use Core\Services\Merchant\XenditService;
use Db\Model\Exception\ModelNotFoundException;
use Db\Transaction;
use Models\BalanceModel;
use Models\RefillModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WithdrawalModel;
use Models\XenditWalletModel;
use Modules\BalanceModule;
use Modules\FeeModule;
use Modules\NotificationsModule;
use Modules\UserModule;
use Serializers\ErrorSerializer;

class Xendit {
    public static function getWithdrawalBanks($request) {
        try {
            $user = getUser($request);
            if (UserModule::isWithdrawDisabled($user)) {
                JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
            }

            $banks = XenditService::getBanks();
            JsonResponse::ok($banks);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('xendit_auth_error');
        }
    }

    public static function getRefillBanks($request) {
        $user = getUser($request);

        $user_virtual_accounts = BalanceModule::getXenditVirtualAccountsByUserId($user->id);
        $user_virtual_accounts_map = [];

        /* @var XenditWalletModel $virtual_account */
        foreach ($user_virtual_accounts as $virtual_account) {
            $user_virtual_accounts_map[$virtual_account->bank_code] = $virtual_account;
        }

        $response = [];
        $banks = XenditService::getBanks();
        foreach ($banks as $bank) {
            $bank_code = $bank['code'];

            $service_provider_code = null;
            if (isset($user_virtual_accounts_map[$bank_code])) {
                $virtual_account = $user_virtual_accounts_map[$bank_code]->account_number;
            } else {
                try {
                    $virtual_account = XenditService::assignVirtualAccount($user->id, $bank_code)->account_number;
                } catch (XenditException $exception) {
                    JsonResponse::errorMessage('generating_xendit_accounts_for_user');
                }
            }

            if ($bank_code === 'MANDIRI') {
                $service_provider_code = substr($virtual_account, 0, 5);
            }

            $response[] = [
                'account_number' => $virtual_account,
                'name' => $bank['name'],
                'code' => $bank['code'],
                'service_provider_code' => $service_provider_code,
                'methods' => self::getListByBank($bank['code'])
            ];
        }

        JsonResponse::ok($response);
    }

    public static function disbursementWebhook($request) {
        /**
         * @var string $external_id
         * @var string $id
         * @var string $bank_code
         * @var string $status
         * @var string $failure_code
         * @var string $amount
         * @var string $id
         */
        extract($request['params']);

        /** @var WithdrawalModel $withdrawal */
        try {
            $withdrawal = WithdrawalModel::get((int) $external_id);
        } catch (ModelNotFoundException $e) {
            JsonResponse::error(['error' => 'Disbursement not found', 'id' => (int) $external_id], 404);
        }

        if ($withdrawal->status !== UserBalanceHistoryModel::STATUS_PENDING) {
            JsonResponse::error(['error' => 'Bad status']);
        }

        // "COMPLETED" or "FAILED"
        $withdrawal->status = array_search(strtolower($status), UserBalanceHistoryModel::STATUSES_MAP);
        $withdrawal->external_id = $id;
        $withdrawal->reject_message = $failure_code ?? null;

        Transaction::wrap(function() use ($withdrawal){
            $withdrawal->save();

            $total = $withdrawal->amount + $withdrawal->fee;
            /** @var BalanceModel $balance */
            $balance = BalanceModel::get($withdrawal->from_id);
            $balance->decrLockedAmount($total);
            if ($withdrawal->status === UserBalanceHistoryModel::STATUS_FAILED) {
                $balance->incrAmount($total);
            }
        });

        JsonResponse::ok();
    }

    public static function refillWebhook($request) {
        /**
         * @var string $payment_id
         * @var string $external_id
         * @var string $owner_id
         * @var string $amount
         * @var string $bank_code
         * @var string $account_number
         * @var string $id
         * @var string $transaction_timestamp
         */
        extract($request['params']);
        try {
            $amount = floatval($amount);
            /** @var XenditWalletModel $xendit_wallet */
            $xendit_wallet = XenditWalletModel::get($external_id);
            /** @var UserModel $user */
            $user = UserModel::get($xendit_wallet->user_id);
            $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);

            $refill_service = new RefillService();
            $refill_service->setCurrency(CURRENCY_IDR)
                ->setAmount($amount)
                ->setBalance($balance)
                ->setBankCode($bank_code)
                ->setProvider('xendit')
                ->setExternalId($payment_id)
                ->setUser($user)
                ->execute();

            $refill_service->sendTelegramNotificationToAdmins();
            JsonResponse::ok(['success' => true]);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('bad_refill');
        }
    }

    public static function virtualAccountChangeWebhook($request) {
        /**
         * @var string $merchant_code
         * @var string $external_id
         * @var string $status
         * @var string $is_closed
         * @var string $bank_code
         * @var string $account_number
         * @var string $id
         */
        extract($request['params']);

        try {
            $xendit_wallet = XenditWalletModel::get($external_id);
        } catch (ModelNotFoundException $e) {
            JsonResponse::errorMessage('Account not found in our system. Account ID: ' . $external_id, Errors::FATAL, false);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('Something went wrong.', Errors::FATAL, false);
        }

        /**
         * @var XenditWalletModel $xendit_wallet
         */
        $xendit_wallet->status = mb_strtolower($status);
        $xendit_wallet->save();

        JsonResponse::ok();
    }

    private static function getListByBank(string $bank): ?array {
        switch ($bank) {
//            case 'BNI':
//                return [
//                    self::generateMethod('refill_bni_atm_method', 12),
//                    self::generateMethod('refill_bni_mobile_banking_method', 8),
//                    self::generateMethod('refill_bni_ibank_personal_method', 10),
//                    self::generateMethod('refill_bni_sms_method', 11),
//                    self::generateMethod('refill_bni_teller_method', 7),
//                    self::generateMethod('refill_bni_agen46_method', 7),
//                    self::generateMethod('refill_bni_atm_bersama_method', 8),
//                    self::generateMethod('refill_bni_other_banks_method', 7),
//                    self::generateMethod('refill_bni_ovo_method', 10),
//                ];
//                break;
            case 'BRI':
                return [
                    self::generateMethod('refill_bri_atm_method', 6),
                    self::generateMethod('refill_bri_ibanking_method', 5),
                    self::generateMethod('refill_bri_mbanking_method', 5),
                ];
                break;
            case 'MANDIRI':
                return [
                    self::generateMethod('refill_mandiri_atm_method', 11),
                    self::generateMethod('refill_mandiri_ibanking_method', 14),
                    self::generateMethod('refill_mandiri_mbanking_method', 11),
                ];
                break;
            case 'PERMATA':
                return [
//                    self::generateMethod('refill_permata_mobile_x_method', 10),
                    self::generateMethod('refill_permata_mobile_method', 10),
//                    self::generateMethod('refill_permata_internet_banking_method', 11),
//                    self::generateMethod('refill_permata_internet_banking_method', 11),
//                    self::generateMethod('refill_permata_atm_bersama_method', 9),
//                    self::generateMethod('refill_permata_atm_prima_method', 8),
//                    self::generateMethod('refill_permata_atm_alto_method', 9),
//                    self::generateMethod('refill_permata_atm_link_method', 9),
                ];
        }
        return null;
    }

    private static function generateMethod(string $method, int $steps): array {
        return [
            'name' => lang($method),
            'steps' => self::generateSteps($method, $steps)
        ];
    }

    private static function generateSteps(string $prefix, int $steps): array {
        $arr = [];
        for ($i = 1; $i <= $steps; $i++) {
            $arr[] = lang($prefix . '_step_' . $i);
        }
        return $arr;
    }
}
