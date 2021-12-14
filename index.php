<?php
// @DEPRECATED
require_once __DIR__ . "/vendor/autoload.php";

use App\Client\ApiTester;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

try {
    $tester = new ApiTester(__DIR__ . "/config.yaml");
    $tester->run();
} catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface |
TransportExceptionInterface | \Exception $e) {
    var_dump($e->getMessage());
}