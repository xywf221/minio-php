<?php


use GuzzleHttp\Psr7\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use xywf221\Minio\Client;
use xywf221\Minio\Credentials;
use xywf221\Minio\Signature\SignatureV4;

require_once './vendor/autoload.php';

$client = new Client([
    'endpoint' => 'minio-ra.proce.top',
    'accessKey' => '6IZWMG2D8k5yAflOwA7w',
    'secretKey' => 'O1ZXDdkFzf1epioPDb7QEXNEKS5GJcespa5cnvrp',
    'secure' => true
]);

//$client->makeBucket('php-create-bucket');
//var_dump($client->listBuckets());
//var_dump($client->bucketExists('php-create-bucket'));
$client->removeBucket('php-create-bucket');