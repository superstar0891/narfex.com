<?php

namespace Core\Services\Merchant;

use Core\Services\Telegram\SendService;
use Db\Transaction;
use Db\Where;
use Models\BankCardModel;
use Models\BankCardOperationModel;
use Models\UserModel;
use Modules\FeeModule;

class CardsService {
    const REFILL_FEE = 0;
    const REFILL_PERCENT_FEE = 3;

    const MAX_AMOUNT_PER_CARD = 200000;

    const BOOK_TIME = 3600 * 2;

    const BANKS = [
        'tinkoff' => [
            'name' => 'Тинькофф банк'
        ],
    ];

    private  static function moderatorLogins(): array {
        return [3928 => 'SuleimanOff', 3765 => 'Eugene_ggg', 3754 => 'Fakelmen',];
    }

    public static function getBankCodes(): array {
        return array_keys(self::BANKS);
    }

    public static function getBanks(): array {
        $result = [];
        foreach (self::BANKS as $code => $info) {
            $result[] = [
                'code' => $code,
                'name' => $info['name'],
            ];
        }

        return $result;
    }

    public static function getUserReservation(UserModel $user): ?array {
        $card = BankCardModel::first(Where::equal('booked_by', $user->id));
        /* @var BankCardModel $card */

        if (!$card) {
            return null;
        }

        $operation = BankCardOperationModel::first(
            Where::and()
                ->set(Where::equal('card_id', $card->id))
                ->set(Where::equal('user_id', $user->id))
                ->set(Where::in('status', [
                    BankCardOperationModel::STATUS_WAIT_FOR_REVIEW,
                    BankCardOperationModel::STATUS_WAIT_FOR_ADMIN_REVIEW,
                    BankCardOperationModel::STATUS_WAIT_FOR_PAY,
                ]))
        );

        if (!$operation) {
            return null;
        }

        return [
            'card' => $card,
            'operation' => $operation,
        ];
    }

    public static function reservation(UserModel $user, string $bank_code, float $amount): ?array {
        $card = BankCardModel::first(Where::and()
            ->set('managed_by', Where::OperatorIsNot, NULL)
            ->set(Where::equal('active', 1))
            ->set('booked_by', Where::OperatorIs, NULL)
            ->set(Where::equal('bank', $bank_code))
            ->set('balance + ' . $amount, Where::OperatorLowerEq, self::MAX_AMOUNT_PER_CARD)
        );
        /* @var BankCardModel $card */

        if (!$card) {
            return null;
        }

        $operation = Transaction::wrap(function () use ($user, $card, $amount) {
            $fee = FeeModule::getFee($amount, CURRENCY_RUB);

            $card->booked_by = $user->id;
            $card->book_expiration = time() + self::BOOK_TIME;
            $card->save();

            $operation = new BankCardOperationModel();
            $operation->card_id = $card->id;
            $operation->amount = $amount;
            $operation->fee = $fee;
            $operation->operation = BankCardOperationModel::OPERATION_BOOK;
            $operation->status = BankCardOperationModel::STATUS_WAIT_FOR_PAY;
            $operation->user_id = $user->id;
            $operation->save();

            return $operation;
        });

        return [
            'card' => $card,
            'operation' => $operation,
        ];
    }

    public static function cancelReservation(UserModel $user, int $reservation_id) {
        $operation = BankCardOperationModel::first(Where::and()
            ->set(Where::equal('id', $reservation_id))
            ->set(Where::equal('user_id', $user->id))
            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_BOOK))
            ->set(Where::in('status', [BankCardOperationModel::STATUS_WAIT_FOR_PAY, BankCardOperationModel::STATUS_WAIT_FOR_REVIEW]))
        );

        if (!$operation) {
            throw new \Exception('Operation not found');
        }

        /* @var BankCardOperationModel $operation */

        $card = BankCardModel::first(Where::equal('id', $operation->card_id));
        /* @var BankCardModel $card */

        Transaction::wrap(function () use ($user, $card, $operation) {

            if ($card && $card->booked_by == $user->id) {
                $card->booked_by = null;
                $card->book_expiration = null;
                $card->save();
            }

            $operation->status = BankCardOperationModel::STATUS_CANCELLED;
            $operation->save();
        });
    }

    public static function confirmPayment(UserModel $user, int $reservation_id) {
        $operation = BankCardOperationModel::first(Where::and()
            ->set(Where::equal('id', $reservation_id))
            ->set(Where::equal('user_id', $user->id))
            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_BOOK))
            ->set(Where::equal('status', BankCardOperationModel::STATUS_WAIT_FOR_PAY))
        );

        if (!$operation) {
            throw new \Exception('Operation not found');
        }

        /* @var BankCardOperationModel $operation */
        $operation->status = BankCardOperationModel::STATUS_WAIT_FOR_REVIEW;
        $operation->save();

        try {
            $send_service = new SendService(SendService::CHAT_CARDS);
            $send_service->sendMessage(
                '#confirm_payment '
                . PHP_EOL .
                sprintf('New payment, moderator - %s', self::getModeratorInfo($operation->card_id))
            );
        } catch (\Exception $e) {
            //
        }
    }

    private static function getModeratorInfo(int $card_id): string {
        $card = BankCardModel::get($card_id);
        $moderator_logins = self::moderatorLogins();

        $login = array_get_val($moderator_logins, $card->managed_by);
        if (is_null($login)) {
            $user = UserModel::get($card->managed_by);
            $login = $user->login;
        } else {
            $login = "@{$login}";
        }

        return "{$login} ({$card->managed_by})";
    }
}
