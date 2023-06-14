<?php

namespace xywf221\Minio\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\Request;
use xywf221\Minio\Constant;

class RequestAmzContent
{
    public static function create(): Closure
    {
        return static function (callable $handler) {
            return static function (
                Request $request,
                array   $options
            ) use ($handler) {
                // 这里等一个如果启用才会根据content-sha256签名
                $body = $request->getBody();
                if ($body->getSize() == 0) {
                    $request = $request->withHeader('X-Amz-Content-Sha256', Constant::UnsignedPayload);
                } else {
                    $ctx = hash_init('sha256');
                    hash_update($ctx, $body->getContents());
                    $hex = hash_final($ctx);
                    $request = $request->withHeader('X-Amz-Content-Sha256', $hex);
                }
                return $handler($request, $options);
            };
        };
    }
}