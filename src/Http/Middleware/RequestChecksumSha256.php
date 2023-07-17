<?php

namespace xywf221\Minio\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\Request;
use xywf221\Minio\Constant;
use function xywf221\Minio\hash_stream;

class RequestChecksumSha256
{
    public static function create(): Closure
    {
        return static function (callable $handler) {
            return static function (
                Request $request,
                array   $options
            ) use ($handler) {
                // 这里等一个如果启用才会根据content-sha256签名
                if ($options['enableCheckSumSha256'] ?? false) {
                    $body = $request->getBody();
                    $sha256sum = hash_stream('sha256', $body);
                } else {
                    $sha256sum = Constant::UnsignedPayload;
                }
                $request = $request->withHeader('X-Amz-Content-Sha256', $sha256sum);
                return $handler($request, $options);
            };
        };
    }
}