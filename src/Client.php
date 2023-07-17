<?php

namespace xywf221\Minio;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use xywf221\Minio\Exception\InvalidContentTypeException;
use xywf221\Minio\Http\BodySummarizer;
use xywf221\Minio\Http\Middleware\RequestChecksumSha256;
use xywf221\Minio\Http\Middleware\RequestSignatureV4;
use xywf221\Minio\Message\ListAllMyBucketsResult;
use xywf221\Minio\Message\LocationConstraint;
use xywf221\Minio\Signature\SignatureV4;


class Client
{
    private HttpClient $httpClient;

    private array $options;

    private Serializer $serializer;


    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->serializer = $this->configureSerializer();
        $this->httpClient = $this->configureHttpClient();
    }

    private function configureHttpClient(): HttpClient
    {
        $uri = (new Uri())->withHost($this->options['endpoint'])
            ->withScheme($this->options['secure'] ? 'https' : 'http');

        $stack = new HandlerStack(Utils::chooseHandler());
        $bodySummarizer = new BodySummarizer($this->serializer);
        $stack->push(Middleware::httpErrors($bodySummarizer), 'http_errors');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        $stack->push(RequestChecksumSha256::create());
        $stack->push(RequestSignatureV4::create(new SignatureV4()));

        $credentials = new Credentials($this->options['accessKey'], $this->options['secretKey'], $this->options['sessionToken']);

        $config = [
            'handler' => $stack,
            'base_uri' => $uri,
            'headers' => [
                'User-Agent' => $this->getUserAgent()
            ],
            'location' => 'us-east-1',
            'serviceType' => Constant::ServiceTypeS3,
            'verify' => false,
            'credentials' => $credentials
        ];
        return new HttpClient($config);
    }

    private function configureSerializer(): Serializer
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader());
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            normalizers: [
                new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, propertyTypeExtractor: new PhpStanExtractor()),
                new ArrayDenormalizer()
            ],
            encoders: [new XmlEncoder()]
        );
    }


    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'secure' => true,
            'sessionToken' => '',
        ]);
        $resolver->setRequired(['endpoint', 'accessKey', 'secretKey']);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidContentTypeException
     */
    public function getBucketLocation(string $bucketName): string
    {
        $response = $this->httpClient->get("$bucketName/?location=");
        $lc = $this->deserializeResponse($response, LocationConstraint::class);
        return match ($lc->getLocation()) {
            '' => Constant::UsEast1,
            'EU' => Constant::UsWest1,
            default => $lc->getLocation(),
        };
    }

    /**
     * @throws GuzzleException
     * @throws InvalidContentTypeException
     */
    public function bucketExists(string $bucketName): bool
    {
        $response = $this->httpClient->head("$bucketName/?location=");
        return $this->deserializeResponse($response, LocationConstraint::class);
    }


    /**
     * @throws GuzzleException
     * @throws InvalidContentTypeException
     */
    public function listBuckets(): ListAllMyBucketsResult
    {
        $response = $this->httpClient->get('/');
        return $this->deserializeResponse($response, ListAllMyBucketsResult::class);
    }

    public function makeBucket()
    {

    }

    /**
     * @param string $bucketName
     * @param string $objectName
     * @param StreamInterface|resource|string $data
     * @param int|null $size
     * @param string|null $contentType
     * @return void
     * @throws GuzzleException
     */
    public function putObject(string $bucketName, string $objectName, mixed $data, int $size = null, string $contentType = null)
    {
        //https://minio-ra.proce.top/test/test.abc?uploads=
        $response = $this->httpClient->post('test/test.abc?uploads=', [
            'headers' => [
                'X-Amz-Checksum-Algorithm' => 'CRC32C',
                'Content-type' => 'application/octet-stream',
            ],
        ]);
        var_dump($response->getBody()->getContents());
    }

    /**
     * @throws InvalidContentTypeException
     */
    private function deserializeResponse(ResponseInterface $response, string $class): object
    {
        // check content type
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType !== 'application/xml') {
            throw new InvalidContentTypeException('Invalid content type: ' . $contentType);
        }
        return $this->serializer->deserialize($response->getBody()->getContents(), $class, 'xml');
    }

    private function getUserAgent(): string
    {
        return sprintf("MinIO (%s; %s) minio-php/v%s", PHP_OS, PHP_OS_FAMILY, PHP_VERSION);
    }
}