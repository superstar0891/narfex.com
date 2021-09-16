<?php

namespace Api\Notifications;

use Api\Errors;
use Core\Response\JsonResponse;
use Db\Where;
use Models\NotificationModel;
use Models\RoleModel;
use Modules\NotificationsModule;
use Serializers\ErrorSerializer;
use Serializers\NotificationSerializer;

class Notifications {
    public static function retrieveList($request) {
        /** @var int $start_from */
        /** @var int $count */
        extract($request['params']);
        $user = getUser($request);
        $page = intval($start_from);
        $result = NotificationsModule::notifications($user, $count, $page);
        JsonResponse::ok($result);
    }

    public static function count($request) {
        $user = getUser($request);

        $count = NotificationModel::queryBuilder()
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('unread', Where::OperatorEq, 1)
            )
            ->get();

        JsonResponse::ok([
            'count' => (int) ($count ? $count['cnt'] : 0),
        ]);
    }

    public static function action($request) {
        /* @var int $id
         * @var string $action
         * @var array $params
         */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\NotificationModel $notify */
            $notify = NotificationModel::get($id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('notification_not_found');
        }

        if ($notify->user_id != $user->id) {
            JsonResponse::errorMessage('access_denied');
        }

        $notify->delete();

        $response = [];
        if ($action === 'accept') {
            switch ($notify->type) {
                case 'agent_invite':
                    /* @var RoleModel $role */
                    $role = RoleModel::get($user->role);
                    if (strtolower($role->role_name) === 'agent') {
                        JsonResponse::errorMessage('notification_accept_agent_invite_already_error');
                    }

                    $role = RoleModel::select(Where::equal('role_name', 'Agent'));
                    if ($role->isEmpty()) {
                        JsonResponse::apiError();
                    }
                    $role = $role->first();

                    $extra = json_decode($notify->extra);

                    $user->agent_date = date('Y-m-d H:i:s');
                    $user->representative_id = (int) $extra->representative_id;
                    $user->role = $role->id;
                    $user->save();

                    $response['message'] = lang('notification_agent_invite_accepted');
                    break;
            }
        }

        JsonResponse::ok($response);
    }

    public static function internalRetrieveList($request) {
        /* @var int $notification_id
         * @var string $action
         * @var array $params
         */
        $user = getUser($request);

        $notifications = [];

        if (!$user->is2FaEnabled()) {
            $notifications[] = NotificationSerializer::internalListItem(
                'google_code',
                lang('internal_notification_google_code'),
                lang('internal_notification_google_code_button')
            );
        }

        if (!$user->secret) {
            $notifications[] = NotificationSerializer::internalListItem(
                'secret_key',
                lang('internal_notification_secret_key'),
                lang('internal_notification_secret_key_button'),
                '/dashboard?modal=secret_key'
            );
        }

        JsonResponse::ok($notifications);
    }
}
