<?php

namespace Modules;

use Db\Model\ModelSet;
use Db\Where;
use Models\NotificationModel;
use Models\ProfitModel;
use Models\RefillModel;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserModel;
use Models\WithdrawalModel;
use Serializers\BalanceHistory\RefillSerializer;
use Serializers\BalanceHistory\WithdrawalSerializer;
use Serializers\PagingSerializer;
use Serializers\BalanceHistory\TransactionSerializer;
use Serializers\BalanceHistory\TransferSerializer;
use Serializers\ProfitSerializer;

class NotificationsModule {
    public static function notifications(UserModel $user, int $count, int $page): array {
        $unread_notification_id = NotificationsModule::minUnreadNotificationId($user->id);
        [$next, $notifications_dataset] = NotificationsModule::notificationByUserId($user, $count, $page);
        $notifications = $notifications_dataset->map('Serializers\NotificationSerializer::listItem');

        /* @var \Models\NotificationModel $notification */
        foreach ($notifications_dataset as $i => $notification) {
            $extra = json_decode($notification->extra);
            $notifications[$i]['actions'] = [];
            switch ($notification->type) {
                case NotificationModel::TYPE_VERIFICATION:
                    $notifications[$i]['message'] = lang($extra->message);
                    break;
                case NotificationModel::TYPE_AGENT_INVITE:
                    $message = str_replace('{login}', $extra->representative_login, 'Пользователь {login} приглашает вас стать Агентом');
                    $notifications[$i]['message'] = $message;
                    $notifications[$i]['actions'] = [
                        [
                            'type' => 'primary',
                            'text' => lang('general_accept'),
                            'action' => 'accept',
                            'params' => [],
                        ],
                        [
                            'type' => 'secondary',
                            'text' => lang('general_cancel'),
                            'action' => 'cancel',
                            'params' => [],
                        ]
                    ];
                    break;
//                case NotificationModel::TYPE_POOL_APPROVED:
//                    $notifications[$i]['message'] = str_replace(
//                        '{amount}',
//                        $extra->amount,
//                        'Ваша заявка в Pool одобрена на сумму {amount}'
//                    );
//                    break;
//                case NotificationModel::TYPE_POOL_DECLINE:
//                    $notifications[$i]['message'] = 'Ваша заявка в Pool отклонена';
//                    break;
            }
        }

        $notifications_unread = array_filter($notifications, function ($row) {
            return !!$row['unread'];
        });

        if (!empty($notifications_unread)) {
            NotificationModel::queryBuilder()
                ->where(Where::and()->set('user_id', Where::OperatorEq, $user->id)->set('unread', Where::OperatorEq, 1))
                ->update([
                    'unread' => 0
                ]);
        }

        if ($user->notifications) {
            $user->notifications = 0;
            $user->save();
        }

        $result = PagingSerializer::detail($next, $notifications);
        $result['unread_notification_id'] = $unread_notification_id;
        return $result;
    }

    public static function minUnreadNotificationId(int $user_id): ?int {
        $unread_notification_id = NotificationModel::queryBuilder()
            ->columns([
                'MIN(id)' => 'unread_notification_id',
            ], true)
            ->where(
                Where::and()
                    ->set('user_id', Where::OperatorEq, $user_id)
                    ->set('type', Where::OperatorNotIN, [NotificationModel::TYPE_POOL_APPROVED, NotificationModel::TYPE_POOL_DECLINE])
                    ->set('unread', Where::OperatorEq, 1)
            )
            ->get();

        return empty($unread_notification_id) ? null : current($unread_notification_id);
    }

    public static function notificationByUserId(UserModel $user, int $count, int $page): array {
        $user_id = $user->id;
        $where = Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('type', Where::OperatorNotIN, [NotificationModel::TYPE_POOL_APPROVED, NotificationModel::TYPE_POOL_DECLINE]);

        $notifications = NotificationModel::queryBuilder()
            ->where($where)
            ->orderBy(['created_at_timestamp' => 'DESC', 'id' => 'DESC'])
            ->paginate($page, $count);

        $next = $notifications->getNext();
        $notifications = $notifications->getItems();

        $models = [
            TransactionModel::class => [],
            TransferModel::class => [],
            RefillModel::class => [],
        ];

        foreach ($notifications as $notification) {
            /** @var NotificationModel $notification */
            switch ($notification->type) {
                case NotificationModel::TYPE_TRANSACTION_RECEIVE:
                case NotificationModel::TYPE_TRANSACTION_SEND:
                    $models[TransactionModel::class][] = $notification->object_id;
                    break;
                case NotificationModel::TYPE_TRANSFER_RECEIVE:
                    $models[TransferModel::class][] = $notification->object_id;
                    break;
                case NotificationModel::TYPE_REFILL:
                    $models[RefillModel::class][] = $notification->object_id;
                    break;
                case NotificationModel::TYPE_WITHDRAWAL:
                    $models[WithdrawalModel::class][] = $notification->object_id;
                    break;
                case NotificationModel::TYPE_SAVING_ACCRUAL:
                    $models[ProfitModel::class][] = $notification->object_id;
                    break;
            }
        }

        foreach ($models as $class => $ids) {
            $ids = array_unique($ids);
            $models[$class] = $class::select(Where::in('id', $ids));
        }

        $users = null;
        if (isset($models[TransferModel::class]) && !empty($models[TransferModel::class])) {
            $transfers_from_users = $models[TransferModel::class]->column('from_user_id');
            $transfers_to_users = $models[TransferModel::class]->column('to_user_id');
            $transfers_users_ids = array_unique(array_merge($transfers_from_users, $transfers_to_users));
            $users = UserModel::select(Where::in('id', $transfers_users_ids), false);
        }

        $notifications->map(function (NotificationModel $notification) use ($models, $user, $users) {
            switch ($notification->type) {
                case NotificationModel::TYPE_TRANSACTION_SEND:
                case NotificationModel::TYPE_TRANSACTION_RECEIVE:
                    /** @var ModelSet $model_set */
                    $model_set = $models[TransactionModel::class];
                    /** @var TransactionModel $model */
                    $model = $model_set->getItem($notification->object_id);
                    $notification->extra = json_encode(TransactionSerializer::serialize($model));
                    break;
                case NotificationModel::TYPE_TRANSFER_RECEIVE:
                    /** @var ModelSet $model_set */
                    $model_set = $models[TransferModel::class];
                    /** @var TransferModel $model */
                    $model = $model_set->getItem($notification->object_id);
                    /** @var UserModel $second_user */
                    $second_user = $users->getItem($model->from_user_id);
                    $model->withUser($second_user);
                    $notification->extra = json_encode(TransferSerializer::serializeWithUser($model, $user));
                    break;
                case NotificationModel::TYPE_REFILL:
                    /** @var ModelSet $model_set */
                    $model_set = $models[RefillModel::class];
                    /** @var RefillModel $model */
                    $model = $model_set->getItem($notification->object_id);
                    $notification->extra = json_encode(RefillSerializer::serialize($model));
                    break;
                case NotificationModel::TYPE_WITHDRAWAL:
                    /** @var ModelSet $model_set */
                    $model_set = $models[WithdrawalModel::class];
                    /** @var WithdrawalModel $model */
                    $model = $model_set->getItem($notification->object_id);
                    $notification->extra = json_encode(WithdrawalSerializer::serialize($model));
                    break;
                case NotificationModel::TYPE_SAVING_ACCRUAL:
                    /** @var ModelSet $model_set */
                    $model_set = $models[ProfitModel::class];
                    /** @var ProfitModel $model */
                    $model = $model_set->getItem($notification->object_id);
                    $notification->extra = json_encode(ProfitSerializer::listItem($model));
                    break;
            }
        });

        return [$next, $notifications];
    }

    public static function send(int $user_id, string $type, array $extra = [], $data = []): NotificationModel {
        $important = isset($data['important']) ? $data['important'] : 0;
        $title = isset($data['title']) ? $data['title'] : null;
        $message = isset($data['message']) ? $data['message'] : null;
        $object_id = isset($data['object_id']) ? $data['object_id'] : null;

        $n = new NotificationModel();
        $n->user_id = $user_id;
        $n->type = $type;
        $n->title = $title;
        $n->message = $message;
        $n->important = $important;
        $n->extra = !empty($extra) ? json_encode($extra) : '';
        $n->object_id = $object_id;
        $n->save();

        UserModel::queryBuilder()
            ->where(Where::equal('id', $user_id))
            ->update([
                'notifications' => 1
            ]);

        return $n;
    }

    public static function sendReceiveNotification(TransactionModel $transaction): NotificationModel {
        return self::send($transaction->user_id, NotificationModel::TYPE_TRANSACTION_RECEIVE, [], ['object_id' => $transaction->id]);
    }

    public static function sendTransactionNotification(TransactionModel $transaction): NotificationModel {
        return self::send($transaction->user_id, NotificationModel::TYPE_TRANSACTION_SEND, [], ['object_id' => $transaction->id]);
    }

    public static function sendTransferNotification(TransferModel $transfer): NotificationModel {
        return self::send($transfer->to_user_id, NotificationModel::TYPE_TRANSFER_RECEIVE, [], ['object_id' => $transfer->id]);
    }

    public static function sendWithdrawalNotification(WithdrawalModel $withdrawal): NotificationModel {
        return self::send($withdrawal->user_id, NotificationModel::TYPE_WITHDRAWAL, [], ['object_id' => $withdrawal->id]);
    }

    public static function sendRefillNotification(RefillModel $refill): NotificationModel {
        return self::send($refill->user_id, NotificationModel::TYPE_REFILL, [], ['object_id' => $refill->id]);
    }

    public static function sendUserAuthorizeNotification(UserModel $user, bool $is_mobile_app = false): NotificationModel {
        $extra = getBrowserInfo();
        unset($extra['user_agent']);
        $extra['is_mobile_application'] = $is_mobile_app;
        $extra['ip_address'] = ipAddress();
        return self::send($user->id, NotificationModel::TYPE_USER_AUTHORIZE, $extra);
    }

    public static function sendSavingAccrualNotification(ProfitModel $profit): NotificationModel {
        return self::send($profit->user_id, NotificationModel::TYPE_SAVING_ACCRUAL, [], ['object_id' => $profit->id]);
    }
}
