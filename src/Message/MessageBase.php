<?php

namespace xywf221\Minio\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;

class MessageBase
{
    #[SerializedName("@xmlns")]
    private string $xmlns = 'http://s3.amazonaws.com/doc/2006-03-01/';

    /**
     * @return string
     */
    public function getXmlns(): string
    {
        return $this->xmlns;
    }
}