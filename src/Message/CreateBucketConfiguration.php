<?php

namespace xywf221\Minio\Message;


class CreateBucketConfiguration extends MessageBase
{

    private string $locationConstraint;

    /**
     * @param string $locationConstraint
     */
    public function __construct(string $locationConstraint)
    {
        $this->locationConstraint = $locationConstraint;
    }


    /**
     * @return string
     */
    public function getLocationConstraint(): string
    {
        return $this->locationConstraint;
    }

    /**
     * @param string $locationConstraint
     */
    public function setLocationConstraint(string $locationConstraint): void
    {
        $this->locationConstraint = $locationConstraint;
    }
}