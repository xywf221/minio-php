<?php

namespace xywf221\Minio;

class Credentials
{
    public function __construct(public string $accessKey, public string $secretKey, public string $sessionToken = '')
    {
    }

}