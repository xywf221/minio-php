<?php

use Symfony\Component\PropertyInfo;
use Symfony\Component\Serializer;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use xywf221\Minio\Client;
use xywf221\Minio\Message\ListAllMyBucketsResult;


require_once './vendor/autoload.php';

$client = new Client([
    'endpoint' => 'minio-ra.proce.top',
    'accessKey' => '6IZWMG2D8k5yAflOwA7w',
    'secretKey' => 'O1ZXDdkFzf1epioPDb7QEXNEKS5GJcespa5cnvrp',
    'secure' => true
]);
//var_dump($client->listBuckets()->getOwner()->getId());
var_dump($client->getBucketLocation('test'));
