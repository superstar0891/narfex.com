<?php

namespace Core\Services\Sumsub;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Models\UserModel;

class SumsubService {
    private $httpClient;
    private $headers;
    private $client_id;
    private $username;
    private $password;
    private $url;

    public function __construct() {
        $this->url = KERNEL_CONFIG['sumsub']['url'];
        $this->client_id = KERNEL_CONFIG['sumsub']['client_id'];
        $this->username = KERNEL_CONFIG['sumsub']['username'];
        $this->password = KERNEL_CONFIG['sumsub']['password'];
        // $this->secureToken = KERNEL_CONFIG['sumsub']['secure_token'];
        $this->httpClient = new Client;
    }

    private function getHeaders() {
        if (!$this->headers) {
            $auth = $this->auth();
            if ($auth && $auth['status'] === 'ok') {
                $this->headers = [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $auth['payload']
                ];
            }
        }

        return $this->headers;
    }

    /**
     * @return array
     */
    private function auth(): ?array {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $response = $this->httpClient->post($this->url . '/resources/auth/login', [
            RequestOptions::HEADERS => $headers,
            RequestOptions::AUTH => [$this->username, $this->password]
        ]);
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody(), true);
    }

    public function applicantCreated(
        string $type,
        string $reviewStatus,
        string $applicantId,
        string $externalUserId
    ) {
        if ($type === 'applicantCreated' && $reviewStatus === 'init'
        ) {
            $user = UserModel::get((int) $externalUserId);
            if ($user) {
                $user->applicant_id = $applicantId;
                $user->verification =  UserModel::USER_UNVERIFIED;
                $user->save();
            }
        }
    }

    public function applicantPending(
        string $type,
        string $reviewStatus,
        string $applicantId,
        string $externalUserId
    ) {
        if ($type === 'applicantPending' && $reviewStatus === 'pending') {
            /**
             * @var UserModel $user
             */
            $user = $user = UserModel::get((int) $externalUserId);
            if ($user) {
                try {
                    $user->applicant_id = $applicantId;
                    $user->verification =  UserModel::USER_VERIFY_PENDING;
                    $user->verification_request_at =  time();
                    $user->save();
                } catch (\Exception $e) {

                }
            }
        }
    }

    public function applicantReviewed(
        string $type,
        string $reviewStatus,
        string $applicantId,
        string $externalUserId,
        array  $reviewResult
    ) {
        if ($type === 'applicantReviewed' && $reviewStatus === 'completed') {
            /**
             * @var $user UserModel
             */
            $user = UserModel::get((int) $externalUserId);

            if ($user) {
                try {
                    $user->applicant_id = $applicantId;
                    if ($reviewResult) {
                        $user->verification_result = json_encode($reviewResult);
                        if ($reviewResult['reviewAnswer'] === 'GREEN') {
                            $user->verification = UserModel::USER_VERIFIED;
                            $this->updateUserData($user, $applicantId);
                        } elseif ($reviewResult['reviewAnswer'] === 'RED') {
                            $user->verification =  (isset($reviewResult['reviewRejectType']) && $reviewResult['reviewRejectType'] === 'FINAL')
                                ? UserModel::USER_REJECTED : UserModel::USER_TEMPORARY_REJECTED;
                        }
                    }
                    $user->save();
                } catch (\Exception $e) {
                }

            }
        }
    }

    public function getAccessToken(UserModel $user) :array {
        try {
            $headers = $this->getHeaders();
            if (isset($headers)) {
                $userId = $user->id;
                $this->httpClient->post($this->url . '/resources/accounts/-/applicantRequests', [
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::JSON => $this->getApplicantRequestData($userId)
                ]);

                $tokenRequestURL = $this->url . "/resources/accessTokens?userId=$userId";
                $accessData = $this->httpClient->post($tokenRequestURL, [
                    'headers' => $headers
                ])->getBody();
                $accessData = json_decode($accessData, true);

                $response = [
                    'success' => isset($accessData['token']),
                    'sumsub' => $this->clientProps($accessData['token'], $userId),
                ];
                return $response;
            }
            return ['success' => false];
        } catch (\Exception $e) {
            return ['success' => false, 'exception' => $e->getMessage()];
        }
    }

    private function updateUserData(UserModel &$user, $applicantId) {
        $applicantData = $this->getApplicantData($applicantId);
        if (isset($applicantData['id']) && is_array($applicantData['info'])) {
            $user->first_name = $applicantData['info']['firstNameEn'] ?? $user->first_name;
            $user->last_name = $applicantData['info']['lastNameEn'] ?? $user->last_name;
            $user->birthday = $applicantData['info']['dob'] ?? $user->birthday;
            $user->country = $applicantData['info']['country'] ?? $user->country;
        }
    }

    private function getApplicantData($applicantId, $headers = null) {
        $headers = $headers ?? $this->getHeaders();
        if (isset($headers)) {
            $url = $this->url . '/resources/applicants/' . $applicantId;
            $data = json_decode($this->httpClient->get($url, [
                RequestOptions::HEADERS => $headers
            ])->getBody(), true);
            if (isset($data) && isset($data['list']) && $data['list']['totalItems'] > 0) {
                return $data['list']['items'][0];
            }
        }
        return null;
    }

    private function clientProps($token, $user_id){
        return [
            'client_id' => $this->client_id,
            'access_token' => $token,
            'user_id' => $user_id
        ];
    }

    private function getApplicantRequestData($userId): array
    {
        return [
            'applicant' => [
                'externalUserId' => $userId,
                'requiredIdDocs' => [
                    'docSets' => [
                        [
                            'idDocSetType' => 'IDENTITY',
                            'types' => ['ID_CARD', 'PASSPORT', 'DRIVERS'],
                            'subTypes' => ['FRONT_SIDE', 'BACK_SIDE']
                        ],
                        [
                            'idDocSetType' => 'SELFIE',
                            'types' => ['SELFIE']
                        ]
                    ]
                ]
            ]
        ];
    }
}
