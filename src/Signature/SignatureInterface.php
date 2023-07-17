<?php

namespace xywf221\Minio\Signature;

use Psr\Http\Message\RequestInterface;

interface SignatureInterface
{

    public function signature(RequestInterface $request, array $config): RequestInterface;

    public function presignedSignature(RequestInterface $request, array $config): RequestInterface;

}