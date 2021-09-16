<?php


namespace Tests;


use Db\Model\Field\PasswordFiled;
use Db\Model\Field\RandomHashField;
use Faker\Factory;
use Models\BalanceModel;
use Models\UserModel;
use Modules\BalanceModule;
use Modules\WalletModule;

class Seeds {
    public static function createUser(?string $login = null, ?string $password = null, ?string $first_name = null, ?string $last_name = null, ?string $email = null, ?int $refer = null) {
        $faker = Factory::create();
        $user = new UserModel();
        $user->first_name = $first_name ?? $faker->firstName;
        $user->last_name = $last_name ?? $faker->lastName;
        $user->email = $email ?? $faker->email;
        $user->login = $login ?? $faker->userName;
        $user->password = PasswordFiled::init()->fill($password ?? $faker->password);
        $user->refer = $refer;
        $user->mail_hash = RandomHashField::init()->fill();
        $user->agent_date = date('Y-m-d H:i:s');
        $user->join_date = date('Y-m-d H:i:s');
        $user->ip = ipAddress();
        $user->role = 4;
        $user->save();

        return $user;
    }

    public static function createWalletsForUser(UserModel $user) {
        WalletModule::generateWallets($user->id, true);
    }

    public static function createUserAndBalanceAndWallet($start_balance_amount = 100000, $start_wallet_amount = 100) {
        $user = Seeds::createUser();

        $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_USD, BalanceModel::CATEGORY_FIAT);
        $balance->incrAmount($start_balance_amount);

        WalletModule::generateWallets($user->id, true);
        $wallet = WalletModule::getWallet($user->id, CURRENCY_BTC);
        $wallet->addAmount($start_wallet_amount);

        return $user;
    }
}
