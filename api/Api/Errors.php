<?php

namespace Api;

class Errors {
    const FATAL = 'fatal';
    const HASH_INCORRECT = 'hash_incorrect';
    const EMAIL_INCORRECT = 'email_incorrect';
    const LOGIN_INCORRECT = 'login_incorrect';
    const ADDRESS_INCORRECT = 'address_incorrect';
    const INSUFFICIENT_FUNDS = 'insufficient_funds';
    const AMOUNT_INCORRECT = 'amount_incorrect';
    const PARAM = 'bad_param';
    const GA_INCORRECT = 'ga_auth_code_incorrect';
    const PASSWORD_INCORRECT = 'password_incorrect';
    const DAY_INCORRECT = 'day_incorrect';
    const WITHDRAW_DISABLED = 'withdraw_disabled';
    const WITHDRAW_MIN_AMOUNT = 'withdraw_min_amount';
    const RECAPTCHA_NEEDED = 'recaptcha_needed';
    const FLOOD = 'flood';
    const INCORRECT_CODE = 'incorrect_code';
    const AMOUNT_TOO_SMALL = 'amount_too_small';
    const DAILY_TRANSACTION_LIMIT = 'daily_transaction_limit';
    const INVALID_PROMO_CODE = 'invalid_promo_code';
}
