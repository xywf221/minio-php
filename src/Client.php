<?php

namespace xywf221\Minio;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Utils;
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
use xywf221\Minio\Http\BodySummarizer;
use xywf221\Minio\Http\Middleware\RequestAmzContent;
use xywf221\Minio\Http\Middleware\RequestSignature;
use xywf221\Minio\Message\ListAllMyBucketsResult;
use xywf221\Minio\Message\LocationConstraint;


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
        $stack->push(Middleware::cookies(), 'cookies');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        $stack->push(RequestAmzContent::create());
        $stack->push(RequestSignature::create(new Credentials($this->options['accessKey'], $this->options['secretKey'], $this->options['sessionToken'])));

        $config = [
            'handler' => $stack,
            'base_uri' => $uri,
            'headers' => [
                'User-Agent' => $this->getUserAgent()
            ],
            'location' => 'us-east-1',
            'serviceType' => Constant::ServiceTypeS3
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
            encoders: ['xml' => new XmlEncoder()]
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
     */
    public function getBucketLocation(string $bucketName): string
    {
        $response = $this->httpClient->get("$bucketName/?location=");
        $lc = $this->deserializeResponse($response->getBody(), LocationConstraint::class);
        return match ($lc->getLocation()) {
            '' => Constant::UsEast1,
            'EU' => Constant::UsWest1,
            default => $lc->getLocation(),
        };
    }

    /**
     * @throws GuzzleException
     */
    public function bucketExists(string $bucketName): bool
    {
        $response = $this->httpClient->head("$bucketName/?location=");
        return $this->deserializeResponse($response->getBody(), LocationConstraint::class);
    }


    /**
     * @throws GuzzleException
     */
    public function listBuckets(): ListAllMyBucketsResult
    {
        $response = $this->httpClient->get('/');
        return $this->deserializeResponse($response->getBody(), ListAllMyBucketsResult::class);
    }

    public function makeBucket(){

    }

    private function deserializeResponse(StreamInterface $body, string $class): mixed
    {
        return $this->serializer->deserialize($body->getContents(), $class, 'xml');
    }

    private function getUserAgent(): string
    {
        return sprintf("MinIO (%s; %s) minio-php/v%s", PHP_OS, PHP_OS_FAMILY, PHP_VERSION);
    }
}