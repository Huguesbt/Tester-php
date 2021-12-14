<?php
require_once __DIR__ . "/vendor/autoload.php";

use App\Client\ApiTester;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

$arguments = getopt("c:");
if(!isset($arguments["c"])){
    echo "Missing config parameter ( -c ) !!\n";
    die(1);
}
if(!file_exists($arguments["c"])){
    echo "Config file not found\nYou could duplicate config.sample.yaml !!\n";
    die(1);
}

try {
    $tester = new ApiTester($arguments["c"]);
    $tester->run();
} catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface |
TransportExceptionInterface | \Exception $e) {
    var_dump($e->getMessage());
}