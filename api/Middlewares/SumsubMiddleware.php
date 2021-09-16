<?php


namespace Middlewares;


use Core\Middleware\MiddlewareInterface;
use Core\Services\Sumsub\Exceptions\SumsubInvalidSignatureException;
use Engine\Parser\Parser;

class SumsubMiddleware implements MiddlewareInterface {

    /**
     * @param $request
     * @throws SumsubInvalidSignatureException
     */
    public function process(&$request) {
        /**
         * @var $request Parser
         */
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $request['data']->getRawInput());
        rewind($resource);
        $resource = stream_get_contents($resource);
        $key = KERNEL_CONFIG['sumsub']['secret_key'];
        $sumsubSignature = isset($_SERVER['HTTP_X_PAYLOAD_DIGEST']) ? $_SERVER['HTTP_X_PAYLOAD_DIGEST'] : null;
        if (!$sumsubSignature || $sumsubSignature !== self::signDataWithSecret($resource, $key)) {
            throw new SumsubInvalidSignatureException();
        }
    }

    /**
     * @param string $data
     * @param string $secretKey
     *
     * @return string
     */
    private static function signDataWithSecret(string $data, string $secretKey): string
    {
        $data = hash_hmac('sha1', $data, $secretKey);

        return $data;
    }

}
