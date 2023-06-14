<?php

namespace xywf221\Minio\Message;

use Symfony\Component\Serializer\Annotation\SerializedPath;


class ListAllMyBucketsResult
{

    /**
     * @var Owner $owner
     */
    private Owner $owner;

    /**
     * @var list<Bucket> $buckets
     */
    #[SerializedPath('[Buckets][Bucket]')]
    private array $buckets;

    /**
     * @return Owner
     */
    public function getOwner(): Owner
    {
        return $this->owner;
    }

    /**
     * @param Owner $owner
     */
    public function setOwner(Owner $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return array
     */
    public function getBuckets(): array
    {
        return $this->buckets;
    }

    /**
     * @param array $buckets
     */
    public function setBuckets(array $buckets): void
    {
        $this->buckets = $buckets;
    }



}