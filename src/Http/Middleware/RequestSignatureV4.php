<?php

namespace xywf221\Minio\Http\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use xywf221\Minio\Constant;
use xywf221\Minio\Signature\SignatureInterface;

class RequestSignatureV4
{


    public static function create(SignatureInterface $signature): \Closure
    {
        return static function (callable $handler) use ($signature) {
            return new self($signature, $handler);
        };
    }

    /**
     * @param SignatureInterface $signature
     * @param callable(RequestInterface, array): PromiseInterface $nextHandler
     */
    public function __construct(private SignatureInterface $signature, private $nextHandler)
    {

    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        if (!isset($options['location'])) {
            throw new InvalidArgumentException('location is required');
        }

        if (!isset($options['serviceType'])) {
            throw new InvalidArgumentException('serviceType is required');
        }
        $request = $this->signature->signature($request, $options);
        return $fn($request, $options);
    }
}