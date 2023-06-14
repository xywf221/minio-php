<?php

namespace xywf221\Minio;

class Constant
{
    const DateIso8601Format = 'Ymd\THis\Z';

    const UnsignedPayload = 'UNSIGNED-PAYLOAD';

    const emptySHA256Hex = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";

    /**
     * @var array<string>
     */
    const V4IgnoredHeaders = [
        "Accept-Encoding",
        "Authorization",
        "User-Agent"
    ];

    const SignV4Algorithm = "AWS4-HMAC-SHA256";

    const ServiceTypeS3 = "s3";

    const ServiceTypeSTS = "sts";

    const UsEast1 = 'us-east-1';

    const UsWest1 = 'eu-west-1';

}