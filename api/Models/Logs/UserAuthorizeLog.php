<?php

namespace Models\Logs;

class UserAuthorizeLog extends LogHelper {
    const USER_AUTHORIZE_ACTION = 'user_authorize';

    public function tableColumn(): string {
        return 'Authorize on platform';
    }
}
