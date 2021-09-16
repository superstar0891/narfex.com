<?php

namespace Serializers;

use Core\App;

class ProfileSerializer {
    /* @param \Models\UserModel $user
     * @return array
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

        $userArr = [
            'id' => (int) $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'login' => $user->login,
            'email' =>  maskEmail($user->email),
            'photo_url' => $image_url,
        ];
        if (!App::isProduction()) {
            $userArr['applicant_id'] = $user->applicant_id;
        }
        return $userArr;
    }
}
