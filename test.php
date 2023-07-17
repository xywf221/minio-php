<?php


use GuzzleHttp\Psr7\Request;
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
$buckets = $client->listBuckets();
var_dump($buckets);

$request = new Request('GET', 'https://minio-ra.proce.top/test/test.abc');

$signature = new SignatureV4();
$request = $signature->presignedSignature($request, [
    'credentials' => new Credentials('6IZWMG2D8k5yAflOwA7w', 'O1ZXDdkFzf1epioPDb7QEXNEKS5GJcespa5cnvrp'),
    'location' => 'us-east-1',
    'sessionToken' => '',
    'expires' => 3600
]);

echo $request->getUri();