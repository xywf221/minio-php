<?php

namespace xywf221\Minio\Message;

class Error
{
    /**
     * @var string
     */
    public string $code;

    /**
     * @var string
     */
    public string $message;

    /**
     * @var string
     */
    public string $bucketName;

    /**
     * @var string
     */
    public string $objectName;

    /**
     * @var string
     */
    public string $resource;

    /**
     * @var string
     */
    public string $requestId;
}