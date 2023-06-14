<?php

namespace xywf221\Minio\Message;

class ErrorResponse
{
    /**
     * @var string
     */
    private string $code;

    /**
     * @var string
     */
    private string $message;

    /**
     * @var string
     */
    private string $bucketName;

    /**
     * @var string
     */
    private string $objectName;

    /**
     * @var string
     */
    private string $resource;

    /**
     * @var string
     */
    private string $requestId;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     */
    public function setBucketName(string $bucketName): void
    {
        $this->bucketName = $bucketName;
    }

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * @param string $objectName
     */
    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource(string $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     */
    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }


}