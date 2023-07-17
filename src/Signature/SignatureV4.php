<?php

namespace xywf221\Minio\Signature;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use xywf221\Minio\Constant;
use xywf221\Minio\Credentials;

class SignatureV4 implements SignatureInterface
{
    /**
     * @param RequestInterface $request
     * @param array $config
     * @return RequestInterface
     */
    public function presignedSignature(RequestInterface $request, array $config): RequestInterface
    {
        if (!isset($config['credentials'])) {
            throw new InvalidArgumentException('credentials is required');
        }
        /** @var Credentials $credentials */
        $credentials = $config['credentials'];

        $now = time();
        $credential = $this->getCredential($credentials->accessKey, $config['location'], $now, Constant::ServiceTypeS3);
        $signedHeaders = $this->getSignedHeaders($request, Constant::V4IgnoredHeaders);
        $query = [
            'X-Amz-Algorithm' => Constant::SignV4Algorithm,
            'X-Amz-Credential' => urlencode($credential),
            'X-Amz-Date' => date(Constant::DateIso8601Format, $now),
            'X-Amz-Expires' => $config['expires'],
            'X-Amz-SignedHeaders' => $signedHeaders,
        ];
        if (!empty($credentials->sessionToken)) {
            $query['X-Amz-Security-Token'] = $credentials->sessionToken;
        }
        $uri = Uri::withQueryValues($request->getUri(), $query);
        $request = $request->withUri($uri);
        $canonicalRequest = $this->getCanonicalRequest($request, Constant::V4IgnoredHeaders, $this->getHashedPayload($request));
        $stringToSign = $this->getStringToSign($config['location'], $now, $canonicalRequest, Constant::ServiceTypeS3);
        $signingKey = $this->getSigningKey($credentials->secretKey, $config['location'], $now, Constant::ServiceTypeS3);
        $signature = $this->getSignature($signingKey, $stringToSign);
        return $request->withUri(Uri::withQueryValue($uri, 'X-Amz-Signature', $signature));
    }

    /**
     * @param RequestInterface $request
     * @param array $config
     * @return RequestInterface
     */
    public function signature(RequestInterface $request, array $config): RequestInterface
    {
        $location = $config['location'];
        $serviceType = $config['serviceType'];
        /** @var Credentials $credentials */
        $credentials = $config['credentials'];

        $now = time();

        $request = $request->withHeader('X-Amz-Date', date(Constant::DateIso8601Format, $now));

        if (!empty($credentials->sessionToken)) {
            $request = $request->withHeader('X-Amz-Security-Token', $credentials->sessionToken);
        }

        $hashedPayload = $this->getHashedPayload($request);
        if ($serviceType == Constant::ServiceTypeSTS) {
            $request = $request->withoutHeader('X-Amz-Content-Sha256');
        }

        $canonicalRequest = $this->getCanonicalRequest($request, Constant::V4IgnoredHeaders, $hashedPayload);

        $stringToSign = $this->getStringToSign($location, $now, $canonicalRequest, $serviceType);

        $signingKey = $this->getSigningKey($credentials->secretKey, $location, $now, $serviceType);

        $credential = $this->getCredential($credentials->accessKey, $location, $now, $serviceType);

        $signedHeaders = $this->getSignedHeaders($request, Constant::V4IgnoredHeaders);

        $signature = $this->getSignature($signingKey, $stringToSign);

        $parts = [
            Constant::SignV4Algorithm . ' Credential=' . $credential,
            'SignedHeaders=' . $signedHeaders,
            'Signature=' . $signature,
        ];

        return $request->withHeader('Authorization', join(', ', $parts));
    }

    private function getSignature(string $signingKey, string $stringToSign): string
    {
        return $this->hmacSha256($signingKey, $stringToSign, false);
    }

    private function getCredential(string $accessKey, string $location, int $timestamp, string $serviceType): string
    {
        $scope = $this->getScope($location, $timestamp, $serviceType);
        return $accessKey . '/' . $scope;
    }

    private function getSigningKey(string $secret, string $location, int $timestamp, string $serviceType): string
    {
        $data = $this->hmacSha256('AWS4' . $secret, date('Ymd', $timestamp));
        $location = $this->hmacSha256($data, $location);
        $service = $this->hmacSha256($location, $serviceType);
        return $this->hmacSha256($service, 'aws4_request');
    }

    /**
     * @param string $key
     * @param string $data
     * @param bool $binary
     * @return string
     */
    private function hmacSha256(string $key, string $data, bool $binary = true): string
    {
        $ctx = hash_init('sha256', HASH_HMAC, $key);
        hash_update($ctx, $data);
        return hash_final($ctx, $binary);
    }


    /**
     * @param string $location
     * @param int $timestamp
     * @param string $canonicalRequest
     * @param string $serviceType
     * @return string
     */
    private function getStringToSign(string $location, int $timestamp, string $canonicalRequest, string $serviceType): string
    {
        $stringToSign = Constant::SignV4Algorithm . "\n" . date(Constant::DateIso8601Format, $timestamp) . "\n";
        $stringToSign .= $this->getScope($location, $timestamp, $serviceType) . "\n";
        $stringToSign .= hash('sha256', $canonicalRequest);
        return $stringToSign;
    }

    /**
     * @param string $location
     * @param int $timestamp
     * @param string $serviceType
     * @return string
     */
    private function getScope(string $location, int $timestamp, string $serviceType): string
    {
        return join('/', [
            date('Ymd', $timestamp),
            $location,
            $serviceType,
            'aws4_request'
        ]);
    }

    private function getHashedPayload(RequestInterface $request): string
    {
        $hashedPayload = $request->getHeaderLine('X-Amz-Content-Sha256');

        if (empty($hashedPayload)) {
            $hashedPayload = Constant::UnsignedPayload;
        }
        return $hashedPayload;
    }

    /**
     * generate a canonical request of style.
     * @param RequestInterface $request
     * @param array $ignoredHeaders
     * @param string $hashedPayload
     * @return string
     */
    private function getCanonicalRequest(RequestInterface $request, array $ignoredHeaders, string $hashedPayload): string
    {
        return join("\n", [
            $request->getMethod(),
            $request->getUri()->getPath(),
            $request->getUri()->getQuery(),
            $this->getCanonicalHeaders($request, $ignoredHeaders),
            $this->getSignedHeaders($request, $ignoredHeaders),
            $hashedPayload
        ]);
    }

    /**
     * getCanonicalHeaders generate a list of request headers for
     * @param RequestInterface $request
     * @param array $ignoredHeaders
     * @return string
     */
    private function getCanonicalHeaders(RequestInterface $request, array $ignoredHeaders): string
    {
        $headers = $request->getHeaders();

        $values = [];

        foreach ($headers as $key => $val) {
            if (in_array($key, $ignoredHeaders)) {
                continue;
            }
            $values[strtolower($key)] = $val;
        }

        ksort($values);
        $buf = '';

        foreach ($values as $key => $val) {
            $buf .= $key . ':';

            if ($key === 'host') {
                if ($request->hasHeader('host')) {
                    $buf .= $request->getHeaderLine('host');
                } else {
                    $buf .= $request->getUri()->getHost();
                }
            } else {
                foreach ($val as $idx => $v) {
                    if ($idx > 0) {
                        $buf .= ',';
                    }
                    $buf .= trim($v);
                }
            }
            $buf .= "\n";
        }
        return $buf;
    }

    private function getSignedHeaders(RequestInterface $request, array $ignoredHeaders): string
    {
        $headers = $request->getHeaders();

        $keys = [];

        foreach ($headers as $key => $val) {
            if (in_array($key, $ignoredHeaders)) {
                continue;
            }
            $keys[] = strtolower($key);
        }

        if (!in_array('host', $keys)) {
            $keys[] = 'host';
        }
        sort($keys);
        return join(';', $keys);
    }
}