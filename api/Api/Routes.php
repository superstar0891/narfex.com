<?php

// vb5tBdnI6mC3bkch

use Core\Middleware\DefaultMiddlewares;
use Core\Route\Route;
use Core\Services\Merchant\XenditService;
use Middlewares\Middlewares;
use \Core\Services\Merchant\CardsService;

$development_routes = \Core\App::isDevelopment() ? include 'StagingRoutes.php' : [];
$api_routes = (
Route::groupMiddleware(DefaultMiddlewares::CORS,
    Route::get('/', 'Api\Test@Test::mainPage'),
    Route::group('/api',
        Route::group('/v1',

            Route::post('/queue', 'Api\Queue@Queue::invoke', [
                'secret' => ['required'],
                'type' => ['required'],
                'params' => ['required'],
            ]),

            Route::group('/sumsub',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/get_access_token', 'Api\Sumsub@Sumsub::getAccessToken')->hide()
                ),
                Route::groupMiddleware(Middlewares::SUMSUB_MIDDLEWARE,
                    Route::post('/reviewed', 'Api\Sumsub@Sumsub::applicantReviewed', [
                        'type' => ['required'],
                        'reviewStatus' => ['required'],
                        'reviewResult' => ['required', 'array'],
                        'applicantId' => ['required'],
                        'externalUserId' => ['required']
                    ])->hide(),
                    Route::post('/created', 'Api\Sumsub@Sumsub::applicantCreated', [
                        'type' => ['required'],
                        'reviewStatus' => ['required'],
                        'applicantId' => ['required'],
                        'externalUserId' => ['required']
                    ])->hide(),
                    Route::post('/pending', 'Api\Sumsub@Sumsub::applicantPending', [
                        'type' => ['required'],
                        'reviewStatus' => ['required'],
                        'applicantId' => ['required'],
                        'externalUserId' => ['required']
                    ])->hide()
                )
            ),

            Route::get('/image', 'Api\Image@retrieve', [
                'object' => ['required'],
            ])->hide(),

            Route::get('/cron', 'Api\Cron@exec', [
                'job' => ['required'],
                'secret' => ['required'],
            ])->hide(),

            Route::group('/balances',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/%n:id', 'Api\Balance@Balance::getBalance', [
                        'id' => ['required', 'int', 'positive']
                    ])
                )
            ),

            // Module `Test`
            Route::get('/ping', 'Api\Test@ping')->hide(),
            Route::groupMiddleware(Middlewares::AdminMiddleware,
                Route::group('/test',
                    Route::get('/', 'Api\Test@Test::retrieve')->hide(),
                    Route::get('/blockchain', 'Api\Test@Test::blockchain', [
                        'currency' => ['required'],
                    ])->hide(),
                    Route::get('/command', 'Api\Test@Test::command')->hide()
                )
            ),
            Route::group('/lang',
                Route::get('/', 'Api\Lang@Lang::retrieve', [
                    'code' => ['required', 'maxLen' => 2]
                ]),
                Route::groupMiddleware(Middlewares::AdminMiddleware,
                    Route::post('/', 'Api\Lang@Lang::edit', [
                        'code' => ['required', 'maxLen' => 2],
                        'key' => ['required'],
                        'value' => ['required'],
                    ])
                ),
                Route::get('/app_export', 'Api\Lang@Lang::appExportRetrieve')
            ),

            Route::group('/crypto',
                Route::get('/notify', 'Api\Crypto@Crypto::notify', [
                    'currency' => ['required', 'oneOf' => ['btc', 'ltc', 'eth']],
                    'txid' => ['required'],
                ])->hide(),
                Route::get('/block_update', 'Api\Crypto@Crypto::blockUpdate', [
                    'currency' => ['required', 'oneOf' => ['btc', 'ltc', 'eth']],
                ])->hide()
            ),

            // Module `Profile`
            Route::group('/profile',
                Route::groupMiddleware(Middlewares::RECAPTCHA_MIDDLEWARE,
                    Route::put('/auth', 'Api\Profile@Profile::sendAuthCode', [
                        'email' => ['required', 'maxLen' => 256, 'email', 'lowercase'],
                    ])
                ),
                Route::get('/verify_auth_code', 'Api\Profile@Profile::verifyAuthCode', [
                    'code' => ['required', 'int', 'positive', 'minLen' => 6, 'maxLen' => 6],
                    'csrf_token' => ['required', 'string'],
                ]),
                Route::groupMiddleware(Middlewares::GoogleAuth,
                    Route::post('/verify_auth_code/2fa', 'Api\Profile@Profile::verifyAuthCode', [
                        'code' => ['required', 'int', 'positive', 'minLen' => 6, 'maxLen' => 6],
                        'csrf_token' => ['required', 'string'],
                    ])
                ),
                Route::get('/verify_mobile_code', 'Api\Profile@Profile::verifyMobileCode', [
                    'code' => ['required', 'int', 'positive', 'minLen' => 6, 'maxLen' => 6],
                    'csrf_token' => ['required', 'string'],
                    'type' => ['required', 'oneOf' => ['sign_up', 'reset_password']],
                ]),
                Route::post('/secret_key', 'Api\Profile@Profile::saveSecretKey', [
                    'login' => ['required'],
                    'password' => ['required'],
                    'public_key' => ['required'],
                    'secret' => ['required', 'minLen' => 10],
                ]),
                Route::post('/reset_ga', 'Api\Profile@Profile::resetGA', [
                    'login' => ['required'],
                    'password' => ['required'],
                    'secret' => ['required'],
                ]),
                Route::post('/reset_password', 'Api\Profile@Profile::resetPassword', [
                    'email' => ['required'],
                ]),
                Route::put('/reset_password', 'Api\Profile@Profile::doResetPassword', [
                    'hash' => ['required'],
                    'password' => ['password', 'required', 'minLen' => 6],
                ]),

                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Profile@Profile::retrieve'),
                    Route::get('/settings', 'Api\Profile@Profile::settingsRetrieve'),
                    Route::post('/upload_photo', 'Api\Profile@Profile::uploadPhoto'),
                    Route::post('/secret_key_logged', 'Api\Profile@Profile::saveSecretKey', [
                        'secret' => ['required', 'minLen' => 10],
                    ]),
                    Route::get('/ga_init', 'Api\Profile@Profile::initGoogleCode'),
                    Route::groupMiddleware(Middlewares::GoogleAuth,
                        Route::put('/change_info', 'Api\Profile@Profile::changeInfo', [
                            'first_name' => ['required', 'username'],
                            'last_name' => ['required', 'username'],
                        ]),
                        Route::put('/change_login', 'Api\Profile@Profile::changeLogin', [
                            'login' => ['required'],
                        ]),
                        Route::post('/change_email', 'Api\Profile@Profile::changeEmail', [
                            'email' => ['required'],
                        ]),
                        Route::post('/change_password', 'Api\Profile@Profile::changePassword', [
                            'old_password' => ['required'],
                            'password' => ['password', 'required', 'minLen' => 6],
                        ]),
                        Route::post('/ga_init', 'Api\Profile@Profile::saveGoogleCode', [
                            'ga_code' => ['required']
                        ])
                    ),
                    Route::post('/confirm_email', 'Api\Profile@Profile::confirmEmail', [
                        'hash' => ['required'],
                    ]),
                    Route::post('/logout', 'Api\Profile@Profile::logout')
                ),
                Route::post('/check_login', 'Api\Profile@Profile::checkLogin', [
                    'login' => ['required', 'minLen' => 3],
                ])
            ),

            // Module `Wallet`
            Route::group('/wallet',
                Route::groupMiddleware(Middlewares::OptionalAuthToken,
                    Route::get('/currencies', 'Api\Wallet@Wallet::currencies')
                ),

                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Wallet@Wallet::retrieve', [
                        'count' => ['default' => 20]
                    ]),
                    Route::get('/%n:id', 'Api\Wallet@Wallet::wallet', [
                        'id' => ['required', 'int', 'positive']
                    ]),
                    Route::get('/send', 'Api\Wallet@Wallet::sendCoinsRetrieve'),
                    Route::get('/transactions', 'Api\Wallet@Wallet::transactionRetrieveList', [
                        'start_from' => ['required'],
                        'count' => ['default' => 20]
                    ]),
                    Route::get('/transfers', 'Api\Wallet@Wallet::transferRetrieveList', [
                        'start_from' => ['required'],
                        'count' => ['default' => 20],
                        'currency' => [],
                        'wallet_id' => [],
                        'order_by' => ['default' => 'desc', 'oneOf' => ['asc', 'desc']]
                    ]),
                    Route::get('/transaction/%n:id', 'Api\Wallet@Wallet::transactionRetrieve', [
                        'id' => ['required', 'int']
                    ]),
                    Route::get('/transfer/%n:id', 'Api\Wallet@Wallet::transferRetrieve', [
                        'id' => ['required', 'int', 'positive']
                    ]),
                    Route::groupMiddleware(Middlewares::GoogleAuth,
                        Route::put('/transaction_send', 'Api\Wallet@Wallet::sendTransaction', [
                            'wallet_id' => ['required', 'positive', 'int'],
                            'address' => ['required', 'maxLen' => 256],
                            'amount' => ['required', 'positive', 'double'],
                        ]),
                        Route::put('/transfer_send', 'Api\Wallet@Wallet::sendTransfer', [
                            'wallet_id' => ['required', 'positive', 'int'],
                            'login' => ['required', 'maxLen' => 256],
                            'amount' => ['required', 'positive', 'double'],
                        ])
                    ),
                    Route::put('/generate', 'Api\Wallet@Wallet::generateAddress', [
                        'currency' => ['required', 'lowercase']
                    ]),
                    Route::post('/buy_token', 'Api\Wallet@Wallet::buyToken', [
                        'currency' => ['required', 'oneOf' => ['btc', 'eth', 'ltc']],
                        'amount' => ['required', 'positive', 'min' => 10],
                        'promo_code' => ['minLen' => 6, 'maxLen' => 10],
                    ]),
                    Route::post('/enable_saving/%n:id', 'Api\Wallet@Wallet::enabledSaving', [
                        'id' => ['required', 'int']
                    ])
                ),

                Route::get('/token_rate', 'Api\Wallet@Wallet::getTokenRate', [
                    'currency' => ['required', 'oneOf' => ['btc', 'eth', 'ltc', 'usd']],
                ]),
                Route::get('/token_sold_amount', 'Api\Wallet@Wallet::tokenSoldAmountRetrieve')
            ),

            // Module `Investments`
            Route::group('/investment',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Investment@Investment::retrieveList'),
                    Route::get('/withdrawal', 'Api\Investment@Investment::withdrawalRetrieveList', [
                        'start_from' => []
                    ]),
                    Route::get('/profit', 'Api\Investment@Investment::profitRetrieveList', [
                        'start_from' => []
                    ]),
                    Route::get('/deposit', 'Api\Investment@Investment::depositRetrieve', [
                        'deposit_id' => ['required', 'positive']
                    ]),
                    Route::get('/calculate', 'Api\Investment@Investment::calculate', [
                        'steps' => [],
                        'amount' => ['required', 'positive'],
                        'currency' => ['required', 'oneOf' => ['btc', 'eth', 'ltc']],
                        'plan_id' => ['required', 'positive'],
                    ]),
                    Route::get('/withdraw', 'Api\Investment@Investment::withdrawRetrieve', [
                        'currency' => ['required'],
                    ]),
                    Route::groupMiddleware(Middlewares::GoogleAuth,
                        Route::put('/withdraw', 'Api\Investment@Investment::withdraw', [
                            'wallet_id' => ['required', 'positive'],
                            'amount' => ['required', 'positive'],
                        ])
                    ),
                    Route::put('/deposit', 'Api\Investment@Investment::openDeposit', [
                        'amount' => ['required', 'positive'],
                        'wallet_id' => ['required', 'positive'],
                        'plan_id' => ['required', 'positive'],
                        'deposit_type' => ['required', 'oneOf' => ['static', 'dynamic']],
                    ]),
                    Route::put('/pool_deposit', 'Api\Investment@Investment::openPoolDeposit', [
                        'amount' => ['required', 'positive'],
                        'wallet_id' => ['required', 'positive'],
                    ]),
                    Route::get('/plans', 'Api\Investment@Investment::plansRetrieve', [
                        'currency' => ['required'],
                        'amount' => ['positive'],
                        'deposit_type' => ['required', 'oneOf' => ['dynamic', 'static', 'pool']]
                    ])
                )
            ),

            // Module `Profit`
            Route::group('/profit',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Profit@Profit::retrieveList', [
                        'offset' => ['required', 'int', 'positive'],
                    ])
                )
            ),

            Route::group('/history',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\History@History::get', [
                        'start_from' => ['default' => 0],
                        'count' => ['default' => 20],
                        'balance_id' => ['positive'],
                        'wallet_id' => ['positive'],
                        'operations' => []
                    ])
                )
            ),

            Route::group('/dashboard',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Dashboard@Dashboard::retrieve')
                )
            ),

            Route::group('/documentation',
                Route::groupMiddleware(Middlewares::OptionalAuthToken,
                    Route::get('/', 'Api\Documentation@Documentation::documentationRetrieve', [
                        'description' => ['bool']
                    ])->hide()
                ),
                Route::get('/schema', 'Api\Documentation@Documentation::schema')
                    ->hide(),
                Route::groupMiddleware(Middlewares::TranslatorMiddleware,
                    Route::post('/method', 'Api\Documentation@Documentation::saveMethodInfo', [
                        'key' => ['required', 'string', 'maxLen' => 150],
                        'short_description' => ['string'],
                        'description' => ['json'],
                        'result' =>  ['json'],
                        'result_example' =>  ['json'],
                        'param_descriptions' => ['json'],
                    ])->hide()
                ),

                Route::get('/method', 'Api\Documentation@Documentation::methodInfoRetrieve', [
                    'key' => ['required', 'string', 'maxLen' => 150]
                ])->hide()
            ),

            Route::group('/notification',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Notifications@Notifications::retrieveList', [
                        'start_from' => [],
                        'count' => ['int', 'default' => 25]
                    ]),
                    Route::get('/unread_count', 'Api\Notifications@Notifications::count'),
                    Route::delete('/', 'Api\Notifications@Notifications::action', [
                        'id' => ['required', 'positive'],
                        'action' => ['required'],
                        'params' => ['json'],
                    ]),
                    Route::get('/internal', 'Api\Notifications@Notifications::internalRetrieveList')
                )
            ),

            Route::group('/page',
                Route::get('/', 'Api\Pages@Pages::retrieve', [
                    'address' => ['required']
                ])->hide(),
                Route::groupMiddleware(Middlewares::TranslatorMiddleware,
                    Route::put('/', 'Api\Pages@Pages::editStaticPage', [
                        'address' => ['required'],
                        'content' => ['required', 'json'],
                        'title' => ['required'],
                        'meta_description' => ['string'],
                        'meta_keywords' => ['string'],
                    ])->hide()
                )
            ),

            Route::group('/partner',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\PartnerPromo@PartnerPromo::retrieve', [
                        'start_from' => ['default' => 0],
                        'count' => ['default' => 20],
                    ]),
                    Route::get('/promo_code', 'Api\PartnerPromo@PartnerPromo::promoCode'),
                    Route::get('/history', 'Api\PartnerPromo@PartnerPromo::history', [
                        'start_from' => ['default' => 0],
                        'count' => ['default' => 20],
                    ]),
                    Route::get('/rating', 'Api\PartnerPromo@PartnerPromo::rating')
                )
            ),

            Route::group('/token',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Token@Token::retrieve')
                )
            ),

            Route::group('/partner_old',
                Route::post('/invite_link_view', 'Api\Partner@Partner::inviteLinkView', [
                    'link' => ['required'],
                ]),
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Partner@Partner::retrieve', [
                        'start_from' => [],
                    ]),
                    Route::get('/clients', 'Api\Partner@Partner::retrievePartnersOnly', [
                        'start_from' => [],
                    ]),
                    Route::get('/profit_chart', 'Api\Partner@Partner::profitChart', [
                        'period' => ['required', 'positive', 'oneOf' => [30, 365]],
                        'agent_id' => ['positive']
                    ]),
                    Route::get('/client_chart', 'Api\Partner@Partner::clientChart', [
                        'period' => ['required', 'positive', 'oneOf' => [30, 365]],
                        'agent_id' => ['positive']
                    ]),
                    Route::put('/invite_link', 'Api\Partner@Partner::createInviteLink', [
                        'name' => ['required'],
                    ]),
                    Route::post('/invite_link', 'Api\Partner@Partner::updateInviteLink', [
                        'id' => ['required', 'positive'],
                        'name' => ['required'],
                    ]),
                    Route::delete('/invite_link', 'Api\Partner@Partner::deleteInviteLink', [
                        'id' => ['required', 'positive'],
                    ]),
                    Route::post('/invite_link_restore', 'Api\Partner@Partner::restoreInviteLink', [
                        'id' => ['required', 'positive'],
                    ]),
                    Route::get('/partner_info', 'Api\Partner@Partner::partnerInfo', [
                        'login' => ['required'],
                    ]),
                    Route::post('/send_invite', 'Api\Partner@Partner::sendInvite', [
                        'login' => ['required'],
                    ])
                )
            ),

            Route::group('/balance',
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/', 'Api\Balance@Balance::retrieve', [
                        'category' => ['required', 'oneOf' => ['exchange', 'partners']],
                    ]),
                    Route::get('/%n:id', 'Api\Balance@Balance::balance', [
                        'id' => ['required', 'int', 'positive']
                    ]),
                    Route::post('/withdraw', 'Api\Balance@Balance::withdraw', [
                        'balance_id' => ['required', 'positive'],
                        'amount' => ['required', 'positive'],
                    ]),
                    Route::post('/deposit', 'Api\Balance@Balance::deposit', [
                        'wallet_id' => ['required', 'positive'],
                        'amount' => ['required', 'positive'],
                    ])
                )
            ),

            Route::group('/exchange',
                Route::groupMiddleware(Middlewares::OptionalAuthToken,
                    Route::get('/', 'Api\Exchange@Exchange::retrieve', [
                        'market' => ['required', 'lowercase'],
                    ])
                ),
                Route::groupMiddleware(Middlewares::ExchangeAuth,
                    Route::put('/order', 'Api\Exchange@Exchange::openOrder', [
                        'market' => ['required', 'lowercase'],
                        'type' => ['required', 'oneOf' => ['limit', 'market']],
                        'action' => ['required', 'oneOf' => ['sell', 'buy']],
                        'amount' => ['required', 'positive'],
                        'price' => ['positive'],
                    ]),
                    Route::delete('/order', 'Api\Exchange@Exchange::cancelOrder', [
                        'order_id' => ['required', 'positive'],
                        'index' => ['int'],
                    ]),
                    Route::get('/open_orders', 'Api\Exchange@Exchange::openOrders', [
                        'market' => ['required', 'lowercase'],
                    ]),
                    Route::get('/orders_history', 'Api\Exchange@Exchange::ordersHistoryRetrieve', [
                        'start_from' => ['required', 'positive'],
                    ]),
                    Route::delete('/cancel_all_orders', 'Api\Exchange@Exchange::cancelAllOrders'),
                    Route::get('/balances', 'Api\Exchange@Exchange::balancesRetrieve')
                ),
                Route::get('/tickers', 'Api\Exchange@Exchange::tickersRetrieve'),
                Route::get('/ticker', 'Api\Exchange@Exchange::tickerRetrieve', [
                    'market' => ['required', 'lowercase'],
                ]),
                Route::get('/markets', 'Api\Exchange@Exchange::marketsRetrieve'),
                Route::get('/order_book', 'Api\Exchange@Exchange::depthRetrieve', [
                    'market' => ['required', 'lowercase'],
                ]),
                Route::get('/trades', 'Api\Exchange@Exchange::tradesRetrieve', [
                    'market' => ['required', 'lowercase'],
                ])
            ),

            Route::group('/exchange_chart',
                Route::get('/config', 'Api\ExchangeChart@ExchangeChart::configRetrieve'),
                Route::get('/symbol_info', 'Api\ExchangeChart@ExchangeChart::symbolInfoRetrieve', [
                    'group' => ['required', 'lowercase'],
                ]),
                Route::get('/symbols', 'Api\ExchangeChart@ExchangeChart::symbolsRetrieve', [
                    'symbol' => ['required', 'lowercase'],
                ]),
                Route::get('/history', 'Api\ExchangeChart@ExchangeChart::historyRetrieve', [
                    'symbol' => ['required', 'lowercase'],
                    'resolution' => ['required'],
                    'from' => ['int'],
                    'to' => ['int'],
                ])
            ),

            Route::group('/api_keys', Route::groupMiddleware(Middlewares::AuthToken,
                Route::get('/', 'Api\ApiKeys@ApiKeys::retrieve'),
                Route::groupMiddleware(Middlewares::GoogleAuth,
                    Route::put('/', 'Api\ApiKeys@ApiKeys::createKey', [
                        'name' => ['required'],
                        'allow_ips' => [],
                        'permission_trading' => ['number'],
                        'permission_withdraw' => ['number'],
                    ]),
                    Route::delete('/', 'Api\ApiKeys@ApiKeys::deleteKey', [
                        'key_id' => ['required', 'positive'],
                    ]),
                    Route::post('/', 'Api\ApiKeys@ApiKeys::editKey', [
                        'key_id' => ['required', 'positive'],
                        'name' => ['required'],
                        'allow_ips' => [],
                        'permission_trading' => ['number'],
                        'permission_withdraw' => ['number'],
                    ]),
                    Route::get('/secret', 'Api\ApiKeys@ApiKeys::secretKeyRetrieve', [
                        'key_id' => ['required', 'positive'],
                    ])
                )
            )),

            Route::group('/fast_exchange',
                Route::get('/rates/xml', 'Api\Bitcoinovnet@rateInXml')->hide(),
                Route::get('/rates/xml/download', 'Api\Bitcoinovnet@rateInXmlDownload')->hide(),
                Route::get('/profit_in_percent', 'Api\Bitcoinovnet@profitInPercent')->hide(),
                Route::groupMiddleware(Middlewares::BitcoinovnetSessionHash,
                    Route::groupMiddleware(Middlewares::OPTIONAL_AUTH_BITCOINOVNET,
                        Route::get('/info', 'Api\Bitcoinovnet@mainInfo')->hide(),
                        Route::post('/reservation', 'Api\Bitcoinovnet@reservation', [
                            'amount' => ['required', 'positive'],
                            'card_number' => ['minLen' => 16, 'maxLen' => 20],
                            'card_owner_name' => ['minLen' => 2],
                            'wallet_address' => ['required'],
                            'email' => ['email'],
                            'promo_code' => ['minLen' => 6, 'maxLen' => 10],
                            'request_id' => [],
                            'session_hash' => [],
                            'card_id' => ['positive'],
                        ])->hide(),
                        Route::get('/reservation', 'Api\Bitcoinovnet@retrieve', [
                            'request_id' => ['required'],
                            'session_hash' => ['required'],
                        ])->hide(),
                        Route::delete('/reservation', 'Api\Bitcoinovnet@cancelReservation', [
                            'request_id' => ['required'],
                            'session_hash' => ['required'],
                        ])->hide(),
                        Route::post('/validate_card', 'Api\Bitcoinovnet@validateCard', [
                            'request_id' => ['required'],
                            'session_hash' => ['required'],
                        ])->hide(),
                        Route::post('/reservation/confirm_payment', 'Api\Bitcoinovnet@confirmPayment', [
                            'request_id' => ['required'],
                            'session_hash' => ['required'],
                        ])->hide(),
                        Route::post('/reservation/update_rate', 'Api\Bitcoinovnet@updateRate', [
                            'request_id' => ['required'],
                            'session_hash' => ['required'],
                        ])->hide(),
                        Route::get('/promo_rate', 'Api\Bitcoinovnet@getRateWithPromoCode', [
                            'promo_code' => ['required'],
                        ])->hide()
                    )
                )
            ),
            Route::group('/reviews',
                 Route::get('/', 'Api\Bitcoinovnet@reviews', [
                     'page' => ['default' => 0, 'positive'],
                     'count' => ['default' => 50, 'positive'],
                 ])->hide(),
                Route::groupMiddleware(Middlewares::RECAPTCHA_MIDDLEWARE,
                    Route::post('/', 'Api\Bitcoinovnet@newReview', [
                        'name' => ['required', 'maxLen' => 255, 'minLen' => 2],
                        'content' => ['required'],
                    ])->hide()
                )
            ),
            Route::group('/cabinet',
                Route::groupMiddleware(Middlewares::BitcoinovnetAuth,
                    Route::group('/partners',
                        Route::get('/', 'Api\Bitcoinovnet@partnersCabinet', [
                            'start_from' => ['default' => 0, 'positive'],
                            'count' => ['default' => 50, 'positive'],
                        ])->hide(),
                        Route::get('/history', 'Api\Bitcoinovnet@partnersCabinetHistory', [
                            'start_from' => ['default' => 0, 'positive'],
                            'count' => ['default' => 50, 'positive'],
                        ])->hide(),
                        Route::post('/withdrawal', 'Api\Bitcoinovnet@partnersCabinetWithdrawal', [
                            'amount' => ['required', 'double', 'positive'],
                            'card_number' => ['required', 'minLen' => 16, 'maxLen' => 20]
                        ])->hide()
                    ),
                    Route::get('/', 'Api\Bitcoinovnet@cabinet', [
                        'start_from' => ['default' => 0, 'positive'],
                        'count' => ['default' => 50, 'positive'],
                    ])->hide(),
                    Route::get('/history', 'Api\Bitcoinovnet@cabinetHistory', [
                        'start_from' => ['default' => 0, 'positive'],
                        'count' => ['default' => 50, 'positive'],
                    ])->hide(),
                    Route::get('/cards', 'Api\Bitcoinovnet@cabinetCards')->hide(),
                    Route::delete('/cards/%n:id', 'Api\Bitcoinovnet@cabinetCardDelete', [
                        'id' => ['required', 'int', 'positive']
                    ])->hide(),
                    Route::post('/logout', 'Api\Profile@Profile::logout')->hide()
                )
            ),
            Route::group('/fiat_wallet',
                Route::group('/cards',
                    Route::groupMiddleware(Middlewares::AuthToken,
                        Route::get('/refill_banks', 'Api\Cards@refillBanksRetrieve'),
                        Route::post('/reservation', 'Api\Cards@reservation', [
                            'amount' => ['required', 'positive'],
                            'bank_code' => ['required', 'oneOf' => CardsService::getBankCodes()],
                        ]),
                        Route::delete('/reservation', 'Api\Cards@cancelReservation', [
                            'reservation_id' => ['required', 'positive'],
                        ]),
                        Route::post('/reservation/confirm_payment', 'Api\Cards@confirmPayment', [
                            'reservation_id' => ['required', 'positive'],
                        ])
                    )
                ),
                Route::group('/xendit',
                    Route::groupMiddleware(Middlewares::XENDIT_MIDDLEWARE,
                        Route::post('/disbursements/webhook', 'Api\Xendit@Xendit::disbursementWebhook', [
                            'external_id' => ['required'],
                            'amount' => ['required'],
                            'bank_code' => ['required'],
                            'status' => ['required'],
                            'failure_code' => [],
                            'id' => ['required']
                        ]),
                        Route::post('/refill/webhook', 'Api\Xendit@Xendit::refillWebhook', [
                            'payment_id' => ['required'],
                            'external_id' => ['required'],
                            'owner_id' => ['required'],
                            'amount' => ['required'],
                            'bank_code' => ['required'],
                            'account_number' => ['required'],
                            'id' => ['required'],
                            'transaction_timestamp' => ['required'],
                        ]),
                        Route::post('/virtual_account/webhook', 'Api\Xendit@Xendit::virtualAccountChangeWebhook', [
                            'external_id' => ['required'],
                            'merchant_code' => ['required'],
                            'name' => ['required'],
                            'bank_code' => ['required'],
                            'account_number' => ['required'],
                            'id' => ['required'],
                            'status' => ['required'],
                        ])
                    ),
                    Route::groupMiddleware(Middlewares::AuthToken,
                        Route::get('/withdrawal_banks', 'Api\Xendit@Xendit::getWithdrawalBanks'),
                        Route::get('/refill_banks', 'Api\Xendit@Xendit::getRefillBanks')
                    )
                ),
                Route::groupMiddleware(Middlewares::AuthToken,
                    Route::get('/balances', 'Api\Balance@Balance::getBalances'),
                    Route::get('/', 'Api\FiatWallet@retrieve'),
                    Route::get('/transactions', 'Api\FiatWallet@getTransactions', [
                        'balance_id' => [],
                        'start_from' => ['default' => 0],
                        'count' => ['default' => 20],
                        'order_by' => ['default' => 'desc', 'oneOf' => ['asc', 'desc']]
                    ]),
                    Route::post('/exchange', 'Api\FiatWallet@exchange', [
                        'from_currency' => ['required'],
                        'to_currency' => ['required'],
                        'amount' => ['required', 'positive'],
                        'amount_type' => ['required', 'oneOf' => ['fiat', 'crypto']]
                    ]),
                    Route::get('/refill_form', 'Api\FiatWallet@payFormRetrieve', [
                        'merchant' => ['required'],
                        'amount' => ['required', 'positive'],
                        'currency' => ['required', 'lowercase'],
                    ]),
                    Route::get('/refill_methods', 'Api\FiatWallet@payMethodsRetrieve'),
                    Route::groupMiddleware(Middlewares::GoogleAuth,
                        Route::put('/withdraw', 'Api\FiatWallet@withdraw', [
                            'bank_code' => ['required', 'oneOf' => XenditService::AVAILABLE_BANKS],
                            'account_holder_name' => ['required'],
                            'account_number' => ['required'],
                            'amount' => ['required', 'positive'],
                            'balance_id' => ['required', 'positive'],
                            'email_to' => []
                        ])
                    ),
                    Route::get('/withdraw_methods', 'Api\FiatWallet@withdrawMethodsRetrieve')
                ),
                Route::get('/rate', 'Api\FiatWallet@ratesRetrieve', [
                    'base' => ['required'],
                    'currency' => ['required'],
                ]),
                Route::get('/event_adv_cash', 'Api\FiatWallet@advCashPaymentEvent', [
                    'login' => ['required'],
                    'ac_merchant_currency' => ['required'],
                    'ac_amount' => ['required', 'positive'],
                ]),
                Route::post('/event_xendit', 'Api\FiatWallet@xenditPaymentEvent', [
                    'id' => ['required'],
                    'fees_paid_amount' => [],
                ])
            ),

            Route::group('/qiwi',
                Route::groupMiddleware(Middlewares::QIWI_MIDDLEWARE,
                    Route::post('/webhook', 'Api\Qiwi@Qiwi::webhook', [
                        'hookId' => [],
                        'messageId' => [],
                        'payment' => [],
                        'test' => [],
                        'version' => [],
                        'hash' => [],
                    ])->hide()
                )
            ),

            Route::group('/admin',
                Route::groupMiddleware(Middlewares::AdminMiddleware,
                    Route::get('/', 'Api\Admin@Admin::retrieve')->hide(),
                    Route::post('/action', 'Api\Admin@Admin::action', [
                        'action' => ['required'],
                        'params' => ['required', 'json'],
                        'values' => ['required', 'json'],
                    ])->hide(),
                    Route::group('/langs',
                        Route::get('/', 'Api\AdminLangs@AdminLangs::get', [
                            'lang' => ['required'],
                            'type' => ['required', 'oneOf' => ['backend', 'web', 'mobile']],
                            'name' => [],
                            'start_from' => ['default' => 0],
                            'count' => ['default' => null]
                        ])->hide(),
                        Route::post('/', 'Api\AdminLangs@AdminLangs::save', [
                            'items' => ['required', 'json']
                        ])->hide(),
                        Route::delete('/', 'Api\AdminLangs@AdminLangs::delete', [
                            'name' => ['required'],
                            'type' => ['required']
                        ])
                    )
                )
            ),

            Route::group('/bots', Route::groupMiddleware(Middlewares::AdminMiddleware,
                Route::put('/', 'Api\Bots@createBot', [
                    'name' => ['required'],
                ])->hide(),
                Route::put('/exchange', 'Api\Bots@addExchange', [
                    'name' => ['required'],
                    'exchange' => ['required', 'oneOf' => ['bitmex', 'binance']],
                    'key' => ['required'],
                    'secret' => ['required'],
                ])->hide(),
                Route::get('/bot', 'Api\Bots@botRetrieve', [
                    'bot_id' => ['required', 'positive'],
                ])->hide(),
                Route::post('/bot', 'Api\Bots@editBot', [
                    'bot_id' => ['required', 'positive'],
                    'name' => ['required'],
                    'time_frame' => ['required'],
                    'symbol' => ['required'],
                    'trade_amount' => ['required', 'positive'],
                    'max_trade_amount' => ['required', 'positive'],
                    'take_profit' => ['positive'],
                    'exchange' => ['oneOf' => ['bitmex']],
                    'exchange_key' => [],
                    'exchange_secret' => [],
                    'indicators' => ['required', 'json'],
                    'leverage' => ['required', 'positive'],
                    'status' => ['required', 'oneOf' => ['activated', 'deactivated']],
                ])->hide(),
                Route::post('/bot_status', 'Api\Bots@setBotStatus', [
                    'bot_id' => ['required', 'positive'],
                    'status' => ['required', 'oneOf' => ['activated', 'deactivated']],
                ])->hide(),
                Route::get('/', 'Api\Bots@retrieve')->hide(),
                Route::get('/options', 'Api\Bots@optionsRetrieve', [
                    'type' => ['required'],
                ])->hide()
            )),
            $development_routes
        )
    ))
);
