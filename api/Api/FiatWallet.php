<?php

namespace Api\FiatWallet;

use Api\Errors;
use Core\Exceptions\Exchange\AmountTooSmallException;
use Core\Exceptions\Exchange\DailyTransactionsLimitException;
use Core\Exceptions\Exchange\InsufficientFundsException;
use Core\Exceptions\Exchange\RateException;
use Core\Exceptions\Token\TokenException;
use Core\Exceptions\Token\TokenPermissionException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalMinAmountException;
use Core\Exceptions\Withdrawal\BalanceNotFoundException;
use Core\Response\JsonResponse;
use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Core\Services\Exchange\Exchange;
use Core\Services\Withdrawal\WithdrawalService;
use Core\Services\Merchant\XenditService;
use Core\Services\Merchant\CardsService;
use Db\Where;
use Exceptions\WithdrawalRequests\WalletNotFoundException;
use Models\BalanceModel;
use Models\ExternalInvoiceModel;
use Models\FiatInvoiceModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Modules\BalanceModule;
use Modules\FiatWalletModule;
use Modules\StatsModule;
use Modules\UserModule;
use Modules\WalletModule;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\BalanceHistory\SwapSerializer;
use Serializers\BalanceHistory\WithdrawalSerializer;
use Serializers\BalanceSerializers;
use Serializers\ErrorSerializer;
use Serializers\PagingSerializer;

function retrieve($request) {
    $user = getUser($request);

    $balances = BalanceModule::getAvailableFiatBalances($user)
        ->map('Serializers\BalanceSerializers::item');

    $wallets = WalletModule::getWallets($user->id)
        ->map('Serializers\WalletSerializer::listItem');

    $history_getter = new BalanceHistoryGetter;
    $history_getter->setUsersIds([$user->id]);
    $history_getter->setOperations([
        UserBalanceHistoryModel::OPERATION_SWAP,
        UserBalanceHistoryModel::OPERATION_REFILL,
        UserBalanceHistoryModel::OPERATION_WITHDRAWAL,
        UserBalanceHistoryModel::OPERATION_BANK_CARD_REFILL_REJECT,
        UserBalanceHistoryModel::OPERATION_SAVING_ACCRUAL,
        UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION,
    ]);
    $paginator = $history_getter->paginateById(null, 20);

    $available_currencies = array_filter(WalletModel::availableCurrencies(), function($currency){
        return $currency['can_exchange'];
    });
    $can_exchange = array_keys($available_currencies);

    $response = [
        'balances' => $balances,
        'wallets' => $wallets,
        'history' => PagingSerializer::detail($paginator->getNext(), HistorySerializer::serializeItems($paginator->getItems(), $user)),
        'exchange_fee' => KERNEL_CONFIG['fiat']['fee'],
        'can_exchange' => $can_exchange,
    ];

    $reservation = CardsService::getUserReservation($user);
    if ($reservation) {
        $response['card_reservation'] = [
            'reservation' => $reservation['operation']->toJson(),
            'card' => $reservation['card']->toJson(),
        ];
    }

    JsonResponse::ok($response);
}

function ratesRetrieve($request) {
    /* @var string $base
     * @var string $currency
     */
    extract($request['params']);
    JsonResponse::ok([
        'rate' => FiatWalletModule::swapRate($base, $currency)
    ]);
}

function exchange($request) {
    /* @var string $from_currency
     * @var string $to_currency
     * @var double $amount
     * @var string $amount_type
     */
    extract($request['params']);
    $user = getUser($request);

    if (UserModule::isWithdrawDisabled($user)) {
        JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
    }

    try {
        $exchange = new Exchange($from_currency, $to_currency, $amount, $amount_type, $user);
        $exchange_response = $exchange->execute();
    } catch (InsufficientFundsException $e) {
        JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
    } catch (TokenPermissionException $e) {
        JsonResponse::errorMessage('cannot_sell_token');
    } catch (TokenException $e) {
        JsonResponse::errorMessage('cannot_buy_token_here');
    } catch (AmountTooSmallException $e) {
        JsonResponse::errorMessage($e->getTranslatedMessage(), Errors::AMOUNT_TOO_SMALL, false);
    } catch (DailyTransactionsLimitException $e) {
        JsonResponse::errorMessage($e->getTranslatedMessage(), Errors::DAILY_TRANSACTION_LIMIT, false);
    } catch (RateException $e) {
        JsonResponse::apiError();
    } catch (WalletNotFoundException $e) {
        JsonResponse::apiError();
    }

    $response = [
        'history' => SwapSerializer::serialize($exchange_response->getHistoryItem()),
    ];

    $response[$exchange_response->getFromType()] = $exchange_response->getSerializedFrom();
    $response[$exchange_response->getToType()] = $exchange_response->getSerializedTo();

    JsonResponse::ok($response);
}

function payMethodsRetrieve($request) {
    $user = getUser($request);
    $conf = KERNEL_CONFIG['fiat']['refill_limits'];

    if (UserModule::isWithdrawDisabled($user)) {
        JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
    }

    $result = [];

    $result['xendit'] = [
        'currencies' => [
            'idr' => [
                'min_amount' => $conf['idr']['min'],
                'max_amount' => $conf['idr']['max'],
                'fees' => [
                    'min_fee' => settings()->xendit_min_fee,
                    'percent_fee' => settings()->xendit_percent_fee
                ]
            ]
        ]
    ];

    $result['cards'] = [
        'currencies' => [
            'rub' => [
                'min_amount' => $conf['rub']['min'],
                'max_amount' => $conf['rub']['max'],
                'fees' => [
                    'min_fee' => CardsService::REFILL_FEE,
                    'percent_fee' => settings()->rub_refill_percent_fee,
                ]
            ]
        ]
    ];

    JsonResponse::ok([
        'methods' => $result,
    ]);
}

function payFormRetrieve($request) {
    /* @var string $merchant
     * @var double $amount
     * @var string $currency
     */
    extract($request['params']);

    $user = getUser($request);

    if (UserModule::isWithdrawDisabled($user)) {
        JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
    }

    $conf = KERNEL_CONFIG['fiat']['refill_limits'];
    if (!isset($conf[$currency])) {
        JsonResponse::error();
    }

    if ($conf[$currency]['min'] > $amount) {
        JsonResponse::apiError(Errors::AMOUNT_INCORRECT);
    }

    if ($conf[$currency]['max'] < $amount) {
        JsonResponse::apiError(Errors::AMOUNT_INCORRECT);
    }

    $result = [];
    switch ($merchant) {
        case 'advcash':
            $settings = KERNEL_CONFIG['merchant']['adv_cash'];
            $params = [
                'ac_account_email' => $settings['email'],
                'ac_sci_name' => $settings['name'],
                'ac_amount' => $amount,
                'ac_currency' => strtoupper($currency),
                'ac_order_id' => time(),
                'login' => $user->login,
            ];

            $hash = [
                $params['ac_account_email'],
                $params['ac_sci_name'],
                $params['ac_amount'],
                $params['ac_currency'],
                $settings['secret'],
                $params['ac_order_id'],
            ];
            $params['ac_sign'] = hash('sha256', implode(':', $hash));

            $result['url'] = 'https://wallet.advcash.com/sci?' . http_build_query($params);
            break;
        case 'invoice':
            $user = getUser($request);

            $pdf = FiatWalletModule::generateInvoice($user, $amount, $currency);
            if (!$pdf) {
                JsonResponse::apiError(Errors::AMOUNT_INCORRECT);
            }
            $result['file'] = base64_encode($pdf->Output('S'));

            $invoice = FiatInvoiceModel::select(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('amount', Where::OperatorEq, $amount)
                ->set('currency', Where::OperatorEq, $currency)
            );
            if ($invoice->isEmpty()) {
                $invoice = new FiatInvoiceModel();
                $invoice->user_id = $user->id;
                $invoice->amount = $amount;
                $invoice->currency = $currency;
                $invoice->save();
            }
            break;
        case 'xendit':
            $invoiceParams = [
                'external_id' => time() . '_' . $user->id,
                'payer_email' => $user->email,
                'description' => lang('wallet_refill'),
                'amount' => $amount,
                'success_redirect_url' => 'https://narfex.com/merchant/xendit/success',
                'failure_redirect_url' => 'https://narfex.com/merchant/xendit/fail',
                'currency' => 'IDR',
                'payment_methods' => XenditService::AVAILABLE_BANKS
            ];
            $invoice = XenditService::createInvoice($invoiceParams);
            $result['url'] = $invoice['invoice_url'];
            break;
        default:
            JsonResponse::apiError();
    }

    JsonResponse::ok($result);
}

function advCashPaymentEvent($request) {
    /* @var string $login
     * @var string $ac_merchant_currency
     * @var double $ac_amount
     */
    extract($request['params']);

    $hash = [
        $_POST['ac_transfer'],
        $_POST['ac_start_date'],
        $_POST['ac_sci_name'],
        $_POST['ac_src_wallet'],
        $_POST['ac_dest_wallet'],
        $_POST['ac_order_id'],
        $_POST['ac_amount'],
        $_POST['ac_merchant_currency'],
        KERNEL_CONFIG['merchant']['adv_cash']['secret']
    ];

    $key = hash('sha256', implode(':', $hash));
    if ($key !== $_POST['ac_hash']) {
        die('Bad sig');
    }

    $user = UserModel::select(Where::equal('login', $login));
    if ($user->isEmpty()) {
        throw new \Exception();
    }

    $user = $user->first();
    /* @var \Models\UserModel $user */

    $currency = strtolower($ac_merchant_currency);
    $balance = BalanceModule::getBalanceOrCreate($user->id, $currency, BalanceModel::CATEGORY_FIAT);

    FiatWalletModule::addPayment('adv_cash', $balance, $ac_amount, $user);

    JsonResponse::ok('ok');
}

function xenditPaymentEvent($request) {
    /* @var int $id */
    /* @var string $fees_paid_amount */
    extract($request['params']);

//    $invoice = XenditService::getInvoice($id);
//
//    if (!in_array($invoice['status'], ['PAID', 'SETTLED'], true)) {
//        JsonResponse::error('Bad status: ' . $invoice['status']);
//    }
//
//
//    if ($invoice['merchant_name'] !== 'G Fin Tech') {
//        JsonResponse::error('Bad merchant name: ' . $invoice['merchant_name']);
//    }
//
//    $currency = strtolower($invoice['currency']);
//    if (!in_array($currency, KERNEL_CONFIG['fiat']['currencies'], true)) {
//        JsonResponse::error('Bad currency: ' . $currency);
//    }
//
//    $exist = ExternalInvoiceModel::select(Where::and()
//        ->set('invoice_id', Where::OperatorEq, $id)
//        ->set('merchant', Where::OperatorEq, 'xendit')
//    );
//
//    if (!$exist->isEmpty()) {
//        JsonResponse::error('Invoice not exist');
//    }
//
//    $amount = (double) $invoice['paid_amount'];
//
//    $fee_conf = KERNEL_CONFIG['fiat']['xendit_fee'][$currency];
//    $fee = max($fee_conf['min'], $amount * $fee_conf['percent'] / 100);
//
//    [, $user_id] = array_map('intval', explode('_', $invoice['external_id']));
//
//    /* @var \Models\UserModel $user */
//    $user = UserModel::get($user_id);
//
//    $balance = BalanceModule::getBalanceOrCreate($user->id, $currency, BalanceModel::CATEGORY_FIAT);
//
//    FiatWalletModule::addPayment('xendit', $balance, $amount - $fee, $user, $id, $fees_paid_amount);
//    StatsModule::profit('xendit_fee', $fee, 'idr', $user_id);

    JsonResponse::ok('ok');
}

function withdrawMethodsRetrieve($request) {

    $user = getUser($request);

    if (UserModule::isWithdrawDisabled($user)) {
        JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
    }

    $result = [];
    $result['xendit'] = [
        'currencies' => [
            'idr' => [
                'min_amount' => XenditService::MIN_WITHDRAWAL_AMOUNT,
                'max_amount' => XenditService::MAX_WITHDRAWAL_AMOUNT,
                'fees' => [
                    'min_fee' => XenditService::WITHDRAWAL_FEE,
                    'percent_fee' => XenditService::WITHDRAWAL_PERCENT_FEE
                ]
            ]
        ]
    ];

    JsonResponse::ok([
        'methods' => $result
    ]);
}

function withdraw($request) {
    /**
     * @var $bank_code
     * @var $account_number
     * @var $account_holder_name
     * @var $amount
     * @var $email_to
     * @var $balance_id
     */

    extract($request['params']);
    $user = getUser($request);

    if (!floodControl('fiat_withdrawal' . $user->id, KERNEL_CONFIG['flood_control']['fiat_withdrawal'])) {
        JsonResponse::floodControlError();
    }

    if (UserModule::isWithdrawDisabled($user)) {
        JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
    }

    $withdrawal_service = new WithdrawalService();
    $withdrawal_service
        ->setUser($user)
        ->setProvider('xendit')
        ->setCurrency(CURRENCY_IDR)
        ->setBankCode($bank_code)
        ->setAmount($amount)
        ->setAccountNumber($account_number)
        ->setAccountHolderName($account_holder_name)
        ->setEmail($email_to);

    try {
        $withdrawal = $withdrawal_service->execute();
        $balance = $withdrawal_service->getBalance();
    } catch (WithdrawalMinAmountException $e) {
        JsonResponse::errorMessage('incorrect_min_amount');
    } catch (BalanceNotFoundException $e) {
        JsonResponse::errorMessage('balance_not_found');
    } catch (\Core\Exceptions\Withdrawal\InsufficientFundsException $e) {
        JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
    }

    JsonResponse::ok([
        'transaction' => WithdrawalSerializer::serialize($withdrawal),
        'balance' => BalanceSerializers::listItem($balance)
    ]);
}
