<?php

namespace xywf221\Minio;

use Psr\Http\Message\StreamInterface;

function hash_stream(string $algorithm, StreamInterface $stream, int $flag = 0, string $key = '', bool $binary = false): string
{
    $ctx = hash_init($algorithm, $flag, $key);
    while (!$stream->eof()) {
        $chunk = $stream->read(32 * 1024);
        hash_update($ctx, $chunk);
    }
    return hash_final($ctx, $binary);
}