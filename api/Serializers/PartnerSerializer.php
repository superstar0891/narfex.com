<?php

namespace Serializers;

use Models\InviteLinkModel;
use Models\UserModel;

class PartnerSerializer {
    public static function clientChartItem($count, $date) {
        return [
            'count' => (int) $count,
            'date' => strtotime($date),
        ];
    }

    public static function agentPartnerItem(UserModel $client, float $profit, int $deposits_count) {
        return [
            'user' => UserSerializer::detail($client),
            'profit' => (double) $profit,
            'deposits_count' => (int) $deposits_count,
        ];
    }

    public static function representativePartnerItem(UserModel $agent, float $profit, int $partners_count) {
        return [
            'user' => UserSerializer::detail($agent),
            'profit' => (double) $profit,
            'partners_count' => (int) $partners_count,
            'created_at' => $agent->agent_date,
        ];
    }

    public static function inviteLink(InviteLinkModel $link) {
        return [
            'id' => (int) $link->id,
            'name' => $link->name ?: 'Referral Link ' . $link->id,
            'join_count' => (int) $link->join_count,
            'view_count' => (int) $link->view_count,
            'deposits_count' => (int) $link->deposits_count,
            'link' => $link->id ? settings()->host . '/?i=' . $link->encode() : '',
        ];
    }

    public static function currencyProfitItem($currency, $amount) {
        return [
            'amount' => (double) $amount,
            'currency' => $currency,
        ];
    }
}
