<?php


namespace Middlewares;


use Api\Errors;
use Core\App;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;
use Middlewares\Exception\RecaptchaFailedException;
use ReCaptcha\ReCaptcha;

class RecaptchaMiddleware implements MiddlewareInterface {
    /**
     * @param $request
     * @throws RecaptchaFailedException
     */
    public function process(&$request) {
        $recaptcha = new ReCaptcha(KERNEL_CONFIG['captcha']['secret']);
        $gRecaptchaResponse = $request['data']->get('recaptcha_response');
        $response = $recaptcha->verify($gRecaptchaResponse);
        if (!$response->isSuccess() && App::isProduction()) {
            JsonResponse::error(['code' => Errors::RECAPTCHA_NEEDED, 'errors' => $response->getErrorCodes(), 'recaptcha_response' => $gRecaptchaResponse]);
        }
    }
}
