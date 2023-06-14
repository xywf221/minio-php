<?php

namespace xywf221\Minio\Http;

use GuzzleHttp\BodySummarizerInterface;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\Serializer\Serializer;
use xywf221\Minio\Message\ErrorResponse;

class BodySummarizer implements BodySummarizerInterface
{


    public function __construct(private Serializer $serializer)
    {
    }

    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string
    {
        $contentType = $message->getHeaderLine('Content-Type');
        if ($contentType !== 'application/xml') {
            return Message::bodySummary($message);
        }

        $errorResponse = $this->serializer->deserialize($message->getBody()->getContents(), ErrorResponse::class, 'xml');

        return $errorResponse->getMessage();

    }
}