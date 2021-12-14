<?php
require_once __DIR__ . "/vendor/autoload.php";

use App\Client\ApiTester;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

$configsFile = __DIR__ . "/config.yaml";

if(!file_exists($configsFile)){
    echo "Missing configs file\nPlease duplicate config.sample.yaml to config.yaml !!\n";
    die(1);
}

try {
    $tester = new ApiTester($configsFile);
    $tester->run();
} catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface |
TransportExceptionInterface | \Exception $e) {
    var_dump($e->getMessage());
}