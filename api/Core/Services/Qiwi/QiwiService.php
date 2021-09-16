<?php

namespace Core\Services\Qiwi;

use \Exception;
use GuzzleHttp\RequestOptions;

class QiwiService {

    private $client;
    private $authToken;

    const WEBHOOK_URL = '/payment-notifier/v1/hooks',
        BALANCES_URL = '/funding-sources/v2/persons/{personId}/accounts',
        TRANSACTION_GET_URL = '/payment-history/v2/transactions/{transactionId}?type={value}',
        CARD_TRANSFER = '/sinap/api/v2/terms/{ID}/payments',
        CARD_LIMITS = '/qw-limits/v1/persons/{personId}/actual-limits';

    public function __construct(string $authToken) {
        $this->client = new QiwiClient();
        $this->authToken = $authToken;
    }

    /**
     * @param int $hookType
     * @param string $param
     * @param int $txnType
     * @return array
     * @throws Exception
     */
    public function registerHook(int $hookType, string $param, int $txnType): array {
        //$hookType Тип хука. Только 1 - вебхук.
        //$param Адрес сервера обработки вебхуков.
        //$txnType Тип транзакций, по которым будут включены уведомления. Возможные значения:
        //Значения:
        //0 - только входящие транзакции (пополнения)
        //1 - только исходящие транзакции (платежи)
        //2 - все транзакции
        return $this->client->put(
            static::WEBHOOK_URL,
            compact('hookType', 'param', 'txnType'),
            $this->authToken
        );
    }

    /**
     * @param string $hookId
     * @return array
     * @throws Exception
     */
    public function deleteHook(string $hookId): array {
        $url = self::WEBHOOK_URL . "/{$hookId}";
        return $this->client->delete($url, [], $this->authToken);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getActiveHooks(): array {
        $url = self::WEBHOOK_URL . '/active';
        return $this->client->get($url, [], $this->authToken);
    }

    /**
     * @param string $hookId
     * @return array
     * @throws Exception
     */
    public function getSecretKey(string $hookId) {
        $url = self::WEBHOOK_URL . "/{$hookId}/key";
        return $this->client->get($url, [], $this->authToken);
    }

    /**
     * @param string $hookId
     * @return array
     * @throws Exception
     */
    public function changeSecretKey(string $hookId): array {
        $url = self::WEBHOOK_URL . "/{$hookId}/newkey";
        return $this->client->post($url, [], $this->authToken);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function testHook(): array {
        $url = self::WEBHOOK_URL . '/test';
        return $this->client->get($url, [], $this->authToken);
    }

    /**
     * @param string $personId
     * @return array
     * @throws Exception
     */
    public function getBalances(string $personId): array {
        $url = str_replace('{personId}', $personId, self::BALANCES_URL);
        return $this->client->get($url, [], $this->authToken);
    }

    /**
     * @param string $txnId
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function getTransactionInfo(string $txnId, $type): array {
        $url = str_replace(
            ['{transactionId}', '{value}'],
            [$txnId, $type],
            self::TRANSACTION_GET_URL
        );
        return $this->client->get($url, [], $this->authToken);
    }

    /**
     * @param string $cardNumber
     * @return array
     * @throws Exception
     */
    public function getProviderId(string $cardNumber): array {
        //#SUCCESS
        //{
        //  "code": {
        //    "value": "0",
        //    "_name": "NORMAL"
        //  },
        //  "data": null,
        //  "message": "21013",
        //  "messages": null
        //}

        //#ERROR
        //{
        //  "code": {
        //    "value": "2",
        //    "_name": "ERROR"
        //  },
        //  "data": null,
        //  "message": "Неверно введен номер банковской карты. Попробуйте ввести номер еще раз.",
        //  "messages": {}
        //}
        return $this->client
            ->setBaseUrl('https://qiwi.com/card/detect.action')
            ->post('', ['cardNumber' => $cardNumber], null, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-type' => 'multipart/form-data'
                ]
            ]);
    }

    public function transfer(int $providerId, string $cardNumber, float $amount): array {
        //https://developer.qiwi.com/ru/qiwi-wallet-personal/index.html?shell#cards
        $url = str_replace('{ID}', $providerId, self::CARD_TRANSFER);
        //required - payment
        //returned paymentInfo
        //
        //examples
        //https://developer.qiwi.com/ru/qiwi-wallet-personal/index.html?shell#payments_model
        return $this->client->get($url, [
            'id' => 1000*time(), //Client ID
            'sum' => [
                'amount' => $amount,
                'currency' => 643
            ],
            'paymentMethod' => [
                'type' => 'Account',
                'accountId' => 643
            ],
            'fields' => [
                'account' => $cardNumber
            ]
        ], $this->authToken);
    }

    public function getCardLimits(string $personId, array $types) {
        $url = str_replace('{personId}', $personId, self::CARD_LIMITS);
        return $this->client->get($url, ['types' => $types], $this->authToken, ['headers' => []]);
    }
}
