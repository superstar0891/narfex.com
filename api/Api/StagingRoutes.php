<?php

use Core\Route\Route;
use Middlewares\Middlewares;

return (
    Route::group('/development',
        Route::groupMiddleware(Middlewares::AdminMiddleware,
            Route::get('/get_access_token', 'Api\DevelopmentTricks@DevelopmentTricks::getUserToken', [
                'app_id' => ['required'],
                'user_id' => ['required']
            ])->hide()
        )
    )
);


