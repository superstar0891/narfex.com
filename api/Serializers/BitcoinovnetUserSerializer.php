<?php

namespace Serializers;

use Models\UserModel;
use Modules\BitcoinovnetModule;

class BitcoinovnetUserSerializer {
    public static function serialize(UserModel $user) {
        if (!$user->fromBitcoinovnet()) {
            throw new \LogicException();
        }

        $cards = BitcoinovnetModule::userCards($user);
        return [
            'id' => (int) $user->id,
            'email' => $user->email,
            'cards' => $cards->toJson(),
        ];
    }
}
