<?php

namespace Core\Services\Telegram;

use Core\App;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SendService {
    private $http_client;
    private $bot_token;
    private $chat_id;
    private $base_url;

    const METHOD_SEND_MESSAGE = 'sendMessage';

    const CHAT_WITHDRAWALS = 'withdrawals',
        CHAT_BITCOINOVNET = 'bitcoinovnet',
        CHAT_BITCOINOVNET_MANUAL_OPERATOR = 'bitcoinovnet_manual_operator',
        CHAT_CARDS = 'cards';

    private $chat_type = 'withdrawals';

    public function __construct(string $chat_type = 'withdrawals') {
        $this->base_url = KERNEL_CONFIG['telegram']['base_url'];
        $this->chat_id = KERNEL_CONFIG['telegram'][$chat_type]['chat_id'];
        $this->bot_token = KERNEL_CONFIG['telegram'][$chat_type]['bot_token'];

        $this->http_client = new Client;
    }

    private function buildUrl(string $method) {
        return sprintf('%s/%s', $this->base_url . $this->bot_token, $method);
    }

    public function sendMessageSafety($text) {
        try {
            $this->sendMessage($text);
        } catch (\Exception $e) {
            //
        }
    }

    public function sendMessage($text) {
        $is_prod = App::isProduction();
        $text = ($is_prod ? '#production ' : '#development ') . $text;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $response = $this->http_client->post($this->buildUrl(self::METHOD_SEND_MESSAGE), [
            RequestOptions::HEADERS => $headers,
            RequestOptions::JSON => [
                'chat_id' => $this->chat_id,
                'text' => $text,
//                'parse_mode' => null,
//                'disable_web_page_preview' => null,
//                'disable_notification' => null,
//                'reply_to_message_id' => null,
//                'reply_markup' => null,
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody(), true);
    }
}
