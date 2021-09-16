<?php

namespace Api\Sumsub;

use Core\Response\JsonResponse;
use Core\Services\Sumsub\SumsubService;
use Db\Model\Exception\ModelNotFoundException;
use Models\UserModel;

class Sumsub {
    public static function getAccessToken($request) {
        try {
            /**
             * @var $user UserModel
             */
            $user = getUser($request);
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage());
        }
        $response = (new SumsubService())->getAccessToken($user);
        if ($response['success']) {
            JsonResponse::ok($response);
        }

        JsonResponse::error($response);
    }

    public static function applicantCreated($request) {
        /**
         * @var $type
         * @var $reviewStatus
         * @var $applicantId
         * @var $externalUserId
         */
        extract($request['params']);
        try {
            (new SumsubService())->applicantCreated(
                $type,
                $reviewStatus,
                $applicantId,
                $externalUserId
            );
        } catch (ModelNotFoundException $e) {
            JsonResponse::error(['message' => 'User not found'], 404);
        }

        JsonResponse::ok();
    }

    public static function applicantReviewed($request) {
        /**
         * @var $type
         * @var $reviewStatus
         * @var $applicantId
         * @var $externalUserId
         * @var $reviewResult
         */
        extract($request['params']);

        try {
            (new SumsubService())->applicantReviewed(
                $type,
                $reviewStatus,
                $applicantId,
                $externalUserId,
                $reviewResult
            );
        } catch (ModelNotFoundException $e) {
            JsonResponse::error(['message' => 'User not found'], 404);
        }

        JsonResponse::ok();
    }

    public static function applicantPending($request) {
        /**
         * @var $type
         * @var $reviewStatus
         * @var $applicantId
         * @var $externalUserId
         */
        extract($request['params']);

        try {
            (new SumsubService())->applicantPending(
                $type,
                $reviewStatus,
                $applicantId,
                $externalUserId
            );
        } catch (ModelNotFoundException $e) {
            JsonResponse::error(['message' => 'User not found'], 404);
        }
        JsonResponse::ok();
    }
}
