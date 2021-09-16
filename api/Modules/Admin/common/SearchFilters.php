<?php

namespace Admin\common;

use Db\Where;
use Models\UserModel;

class SearchFilters {
    public static function user(array $filters, Where $where, string $user_field = 'user_id') {
        $user = $filters['user'] ?? null;

        if ($user) {
            $user = trim($user);
            $users = UserModel::select(
                Where::and()
                    ->set(Where::equal('platform', PLATFORM_FINDIRI))
                    ->set(
                        Where::or()
                            ->set(Where::equal('id', $user))
                            ->set('login', Where::OperatorLike, "%$user%")
                            ->set('email', Where::OperatorLike, "%$user%")
                            ->set("CONCAT(first_name, ' ', last_name)", Where::OperatorLike, "%$user%")
                    )
            );
            $where->set(Where::in($user_field, $users->column('id')));
        }

        return $where;
    }
}
