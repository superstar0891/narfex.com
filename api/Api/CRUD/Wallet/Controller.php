<?php

namespace Api\CRUD\Wallet;

use Core\Controller\ApiCRUDController;

class WalletCRUD extends ApiCRUDController {
    public static $model = 'Wallet';

    public static $serializer = 'CRUD\Wallet\WalletSerializer';
}
