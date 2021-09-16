<?php

namespace Core\Command;

use ClickHouse\ClickHouse;
use Db\Where;
use Models\LangsModel;
use Core\Services\ExternalExchange\Bitmex;
use Core\Services\Merchant\FastExchangeService;
use Models\CardModel;
use Models\HedgingExAccount;
use Models\ReservedCardModel;
use Modules\HedgingExchangeModule;

class Vasiliev implements CommandInterface {
    private $name;
    private $params;

    function __construct(string $name, ?array $params = null) {
        $this->name = $name;
        $this->params = $params;
    }

    public function exec() {
        echo $this->name . PHP_EOL;
        switch ($this->name) {
            case 'test_levenshtein':
                $text = 'Ы-Кор...Bl0ckcha1r-Un1vers...060spesarens6n0KLC01nMarketCap01Hexadec1maltlетБиткоинов?Купите!3aABKaN0367215978607.01125673BTCKypc06w0ewr0R1su44cex.ВремянаобВерифицигah0dustrePermataBankpeacTaBaeetsamytsapT4640053092721379M0NTH/YEARVAL1DTHRU12/24V1SAsepe@mnp0satapyHaneanaaanp';
                $search1 = '4640053092721379';
                $search2 = '367215978607';

                var_dump(matchSubstrInText($text, $search1));
                var_dump(matchSubstrInText($text, $search2, 2));
                break;
            case 'clickhouse':
                $this->clickhouse();
                break;
            case 'bitmex_balance':
                $short_conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex']['short'];
                $long_conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex']['long'];
                try {
                    $bitmex = new Bitmex($short_conf['key'], $short_conf['secret']);
//                    $bitmex = HedgingExchangeModule::getExchange('bitmex', $short_conf['key'], $long_conf['secret']);
                    dd($bitmex->getBalance());
                } catch (\Exception $e) {
                    dd($e->getTraceAsString());
                }

                break;
            case 'bitmex_long':
                $bitmex = new Bitmex('OZ-YaTwKG606kZf-S8DrwOJM', 'isWNuSsCvbT1u56Ih3io-Q6hGk_uQj0et50r7itUwfrNcF5e');
                try {
                    $order = $bitmex->openMarketOrder(\Symbols::BTCUSD, Bitmex::SIDE_BUY, 2, true);
                } catch (\Exception $e) {
                    dd($e->getMessage());
                }
                echo $order['fee'] . PHP_EOL;
                print_r($order);
                break;

            case 'bitmex_short':
                try {
                    $bitmex = new Bitmex('h033zydX1s9yfcgEokG85pAW', 'tQOwqtloois9mDHlsGvZNVSL5zlyF7Pi1OUm1I-7uN2dp1WB');
                    $order = $bitmex->openMarketOrder(\Symbols::BTCUSD, Bitmex::SIDE_SELL, 1);
                    print_r($order);
                } catch (\Exception $e) {
                    print_r($e->getMessage());
                }
                break;
            case 'check_translate':
                $this->check_translate();
                break;
            case 'delete_langs':
                $this->delete_langs();
                break;
            case 'qiwi_test':
                /** @var CardModel $card */
                $card = CardModel::select()->first();
//                dd(FastExchangeService::getCardBalance($card));
                dd(FastExchangeService::getSecretKey($card));

                //FastExchangeService::changeHook($card);
                break;
            case 'profit_generate':
                $promo_code = 'BTCNET72';
                $promo_code = FastExchangeService::getPromoCodeModel($promo_code);
                if ($promo_code === null) {
                    throw new \Exception('Invalid promo code');
                }
                var_dump($promo_code->user_id);

                for ($i = 0; $i < 100; $i++) {
                    /** @var ReservedCardModel $reservation */
                    $reservation = ReservedCardModel::select(Where::equal('status', 'confirmed'))->last();
                    FastExchangeService::addProfitByReservation($promo_code, $reservation, floatval(random_int(500, 1000)), CURRENCY_RUB);
                }

                break;
            default:
                die('Unknown job: ' . $this->name);
        }
    }

    private function clickhouse() {
        ClickHouse::shared()->exec('ALTER TABLE default.exchange_orders MODIFY COLUMN amount Decimal(22,8)');
        ClickHouse::shared()->exec('ALTER TABLE default.exchange_orders MODIFY COLUMN price Decimal(22,8)');
    }

    private function check_translate() {
        $keys = [
            'api_exchange_small_amount_err', 'api_exchange_large_amount_err', 'wallet_refill', 'api_withdraw_limit_error', 'notification_agent_invite_accepted', 'internal_notification_google_code', 'internal_notification_google_code_button', 'internal_notification_secret_key', 'internal_notification_secret_key_button', 'main_email_change_title', 'main_email_change_notify_title', 'main_email_change_title', 'main_email_change_caption', 'general_confirm', 'refill_bni_atm_method', 'refill_bni_mobile_banking_method', 'refill_bni_ibank_personal_method', 'refill_bni_sms_method', 'refill_bni_teller_method', 'refill_bni_agen46_method', 'refill_bni_atm_bersama_method', 'refill_bni_other_banks_method', 'refill_bni_ovo_method', 'refill_bri_atm_method', 'refill_bri_ibanking_method', 'refill_bri_mbanking_method', 'refill_mandiri_atm_method', 'refill_mandiri_ibanking_method', 'refill_mandiri_mbanking_method', 'refill_permata_mobile_x_method', 'refill_permata_mobile_method', 'refill_permata_internet_banking_method', 'refill_permata_internet_banking_method', 'refill_permata_atm_bersama_method', 'refill_permata_atm_prima_method', 'refill_permata_atm_alto_method', 'refill_permata_atm_link_method', 'access_denied', 'error_404_desc', 'amount_too_small', 'mail_never_share_codes_from_ga_and_mail', 'general_accept', 'general_cancel', 'api_auth_login_or_password_incorrect', 'api_auth_user_banned', 'require_reset_password', 'api_auth_login_or_password_incorrect', 'mail_confirm_restore_password', 'mail_confirm_restore_password', 'general_confirm', 'mail_confirm_email_address_reg', 'general_confirm', 'api_error', 'mail_confirm_email_address', 'verification_you_not_pass', 'verification_you_pass_successfully', 'user_daily_transactions_limit', 'mail_restore_password', 'mail_restore_password', 'mail_confirm_email_address', 'mail_confirm_email_address', 'access_denied', 'access_denied', 'not_found', 'wallet_not_found', 'access_denied', 'access_denied', 'market_not_found', 'market_not_found', 'exchange_no_market_price_error', 'order_not_found', 'access_denied', 'order_already_cancelled', 'order_type_should_be_limit', 'cannot_sell_token', 'cannot_buy_token_here', 'incorrect_min_amount', 'balance_not_found', 'insufficient_funds', 'wallet_not_found', 'balance_not_found', 'deposit_is_empty', 'api_wallet_not_found', 'api_withdrawal_static_deposit_error', 'deposits_disabled', 'api_wallet_not_found', 'access_denied', 'api_investment_plan_not_found', 'api_currency_not_exist', 'deposits_disabled', 'api_wallet_not_found', 'notification_not_found', 'access_denied', 'notification_accept_agent_invite_already_error', 'wrong_role', 'agent_invite_user_not_found', 'agent_invite_already_agent', 'agent_invite_flood', 'agent_invite_already_sent', 'incorrect_code', 'login_must_not_have_symbols', 'login_must_be_least_characters', 'login_already_used', 'login_not_found', 'incorrect_code', 'api_google_code_incorrect', 'api_secret_key_exist_error', 'api_auth_login_or_password_incorrect', 'api_err_secret_incorrect', 'no_user_with_email', 'api_account_password_incorrect', 'access', 'wallet_not_found', 'transaction_not_found', 'transaction_not_found', 'transfer_not_found', 'wallet_not_found', 'address_incorrect', 'address_incorrect', 'insufficient_funds', 'wallet_not_found', 'withdrawal_disabled', 'address_incorrect', 'insufficient_funds', 'incorrect_min_amount', 'xendit_auth_error', 'generating_xendit_accounts_for_user', 'bad_refill', 'api_google_code_incorrect',
            '2fa_already_enabled',
            'field_param_is_required',
            'field_password_not_have_uppercase',
            'field_password_not_have_lowercase',
            'field_password_not_have_number',
            'field_password_not_have_special_symbol',
            'field_password_incorrect_length',
            'field_max_len',
            'field_min_len',
            'field_int',
            'field_bool',
            'field_double',
            'field_positive',
            'field_email',
            'field_one_of',
            'field_max',
            'field_min',
            'field_username',
        ];

        $keys = array_unique($keys);

        $langs_backend = LangsModel::select(Where::and()
            ->set(Where::equal('type', 'backend'))
            ->set(Where::equal('lang', 'en'))
        );

        $created_keys = $langs_backend->column('name');
        $non_created_keys = array_filter($keys, function ($key) use ($created_keys) {
            return !in_array($key, $created_keys);
        });

        print_r(implode(', ', $non_created_keys));
    }

    public function delete_langs() {
        $langs_backend = LangsModel::select(Where::and()
            ->set(Where::equal('type', 'backend'))
        );

        $delete_patterns = [
//            'agent_',
            'module_',
        ];

        foreach ($langs_backend as $lang) {
            /** @var LangsModel $lang*/
            foreach ($delete_patterns as $pattern) {
                if (strpos($lang->name, $pattern) !== false) {
                    $lang->delete(true);
                    continue;
                }
            }
        }
    }
}
