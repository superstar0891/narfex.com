<?php

namespace Serializers;

use Models\NotificationModel;

class NotificationSerializer {
    /* @param \Models\NotificationModel $notification
     * @return array
     */
    public static function listItem(NotificationModel $notification): array {
        $extra = $notification->extra ? json_decode($notification->extra, true) : [];
        return [
            'id' => (int) $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'created_at' => (int) $notification->created_at_timestamp,
            'unread' => (bool) $notification->unread,
            'important' => (bool) $notification->important,
            'icon' => 'https://static.bitcoinbot.pro/img/notify/' . $notification->type . '.png',
            'data' => $extra,
        ];
    }

    public static function internalListItem($type, $caption, $button_text, $link = null, array $params = []): array {
        return [
            'type' => $type,
            'caption' => $caption,
            'button_text' => $button_text,
            'link' => $link,
            'params' => $params,
        ];
    }
}
