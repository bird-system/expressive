<?php

namespace BS\Middleware;

use BS\Utility\Utility;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class ResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $dispatchResult = $request->getAttribute('dispatch_result', null);
        $displayJson = $request->getAttribute('display_json', true);
        if (!is_null($dispatchResult)) {
            $isAcceptJson = Utility::checkAcceptJson($request) && $displayJson;

            if ($dispatchResult instanceof \Exception) {
                if ($isAcceptJson) {
                    return new JsonResponse([
                        'success' => false,
                        'code' => 500,
                        'message' => $dispatchResult->getMessage()
                    ]);
                } else {
                    //go to error handler
                    throw $dispatchResult;
                }
            }

            if ($dispatchResult instanceof ResponseInterface) {
                return $dispatchResult;
            }

            if (is_array($dispatchResult) && $isAcceptJson) {
                return new JsonResponse($dispatchResult);
            }
        }

        return $delegate->process($request);
    }
}