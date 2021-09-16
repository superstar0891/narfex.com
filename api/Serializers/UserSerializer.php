<?php

namespace Serializers;

class UserSerializer {
    /* @param \Models\UserModel $user
     * @return array
     * @throws
     */
    public static function detail($user) {
        if ($user->img) {
            if (substr($user->img, 0, 2) === 'gs') {
                $image_url = 'https://api.narfex.com/api/v1/image?object=' . substr($user->img, 5);
            } else {
                $image_url = 'https://cabinet.bitcoinbot.pro/media/user/' . $user->img . '?size=300';
            }
        } else {
            $image_url = 'https://static.bitcoinbot.pro/img/photo_placeholder.png';
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'login' => $user->login,
            'photo_url' => $image_url,
            'created_at' => $user->join_date,
        ];
    }
}
