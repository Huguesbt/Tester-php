<?php

namespace App\Client;

use App\Asserts\AssertsRequest;
use App\Request\RequestClient;
use Symfony\Component\Yaml\Yaml;

class ApiTester {

    private $auth;
    private $url;
    private $token;
    private $groups;
    private $pathRegex      = '/{([a-zA-Z0-9-]+.+)}/';
    private $results = [];

    /**
     * @throws \Exception
     */
    public function __construct(string $configsFile) {
        $configs = $this->parse($configsFile);

        $this->url    = $configs->url;
        if (isset($configs->auth)) $this->auth   = $configs->auth;
        $this->groups = $configs->groups;

        $this->checkUrl();
    }

    private function parse(string $path): object {
        return Yaml::parseFile($path, Yaml::PARSE_OBJECT_FOR_MAP);
    }

    /**
     * @throws \Exception
     */
    private function checkUrl() {
        if ($this->url == "") {
            throw new \Exception("Url is missing !");
        }
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Exception
     */
    public function run() {
        if ($this->auth !== null) {
            $this->authenticate();
            $this->checkToken();
        }

        foreach ($this->groups as $apiGroup) {
            $this->runGroup($apiGroup);
        }
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    private function authenticate() {
        $request     = $this->makeRequest(
            $this->url . $this->auth->path,
            $this->auth->method,
            null,
            [
                'username' => $this->auth->username,
                'password' => $this->auth->password,
            ]
        );
        $body        = $request->getJsonBodyObject();
        $this->token = isset($body->{$this->auth->tokenName}) ? $body->token : null;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Exception
     */
    private function makeRequest(
        string $url, string $method, string $body = null, array $json = null, array $headers
    = null
    ): RequestClient {
        $request = new RequestClient();
        $request->setUrl($url);
        $request->setMethod($method);
        if ($body !== null) $request->setBody($body);
        if ($json !== null) $request->setJson($json);
        if ($headers !== null) $request->setHeaders($headers);

        $request->send();

        return $request;
    }

    /**
     * @throws \Exception
     */
    private function checkToken() {
        if (null === $this->token) {
            throw new \Exception("Token not found after authenticate request !");
        }
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Exception
     */
    private function runGroup(object $group) {
        $this->results = [];
        $model         = isset($group->model) ? $this->buildModel($group->model, $group->name) : null;

        foreach ($group->routes as $route) {
            try {
                $this->runApiCall(
                    $this->buildUrl($group->prefix . $this->getPath($route->path)),
                    $route->method,
                    $route->name,
                    $model,
                    $route->asserts
                );
            } catch (\AssertionError $e){
                echo "Error: {$route->method} {$route->name} - {$e->getMessage()}";
                die(1);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function buildModel(object $model, string $prefix = "", int $minRandom = 0, int $maxRandom = 10): array {
        $modelBuilt = [];
        foreach ($model as $k => $m) {
            switch ($m) {
                case "object":
                case "array":
                    $modelBuilt[ $k ] = [];
                    break;
                case "int":
                    $modelBuilt[ $k ] = random_int($minRandom, $maxRandom);
                    break;
                case "null":
                    $modelBuilt[ $k ] = null;
                    break;
                case "empty":
                    $modelBuilt[ $k ] = "";
                    break;
                case "phone":
                    $modelBuilt[ $k ] = "+33123456789";
                    break;
                case "address":
                    $modelBuilt[ $k ] = random_int(1, 100) . " rue de l'avant";
                    break;
                case "email":
                    $modelBuilt[ $k ] = uniqid($prefix) . "-test@localhost.lan";
                    break;
                case "postal_code":
                    $modelBuilt[ $k ] = random_int(10, 299).random_int(111, 999);
                    break;
                case "date":
//                    Random date between 1970-01-01 and now minus 20 years
                    $date = random_int(strtotime("1970-01-01"), (new \DateTime())->sub(new \DateInterval('P20Y'))->getTimestamp());
                    $modelBuilt[ $k ] = date('Y-m-d', $date);
                    break;
                case "string":
                    $modelBuilt[ $k ] = uniqid($prefix);
                    break;
                default:
                    $modelBuilt[ $k ] = $m;
            }
        }

        return $modelBuilt;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Exception
     */
    private function runApiCall(string $url, string $method, string $name, array $model, object $asserts = null) {
        $response = $this->runRoute(
            $url,
            $method,
            ($method === "POST") ? $model : null
        );

        var_dump(json_encode($model));
        if ($asserts !== null) {
            new AssertsRequest((object)$response, $asserts);
        }
        $this->results[ $name ] = $response;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    private function runRoute(string $url, string $method, array $body = null): array {
        $request = $this->makeRequest(
            $url,
            $method,
            null,
            $body,
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        return [
            "url"      => $request->getResponseInfo('url'),
            "method"   => $request->getResponseInfo('http_method'),
            "body"     => $body,
            "status"   => $request->getResponsestatusCode(),
            "response" => $request->getJsonBody(),
            "headers"  => $request->getResponseHeaders(),
        ];
    }

    private function buildUrl(string $path): string {
        return $this->url . $path;
    }

    private function getPath(?string $path): string {
        if(null === $path) return "";
        if($path === "/" || $path === "") return $path;
        return $this->buildPath($path);
    }

    private function buildPath(string $path): ?string {
        preg_match($this->pathRegex, $path, $matches);
        if (count($matches) > 1) {
            $m = explode(".", $matches[1]);
            $routeName = array_shift($m);
            $pathes     = $m;

            if ($response = $this->getResponseDataFromRouteName($routeName)) {
                $value = $this->getFieldFromRandomValue($response, $pathes);
                return preg_replace($this->pathRegex, $value, $path);
            } else {
                echo "No results found for $routeName !\n";
                return null;
            }
        } else {
            echo "No matches found for $path with regex {$this->pathRegex} !\n";
            return null;
        }
    }

    private function getFieldFromRandomValue(array $result, array $pathes){
        foreach ($pathes as $path) {
            if (is_array($result)){
                if(!isset($result[$path])){
                    return null;
                }

                $result = $result[$path];
                if (is_array($result)){
                    $result = $result[ array_rand($result) ];
                }
            } elseif (isset($result[$path])){
                $result = $result[$path];
            }
        }
        return $result;
    }

    private function getResponseDataFromRouteName(string $routeName): ?array{
        if(!isset($this->results[ $routeName ])){
            return null;
        }
        if(!isset($this->results[ $routeName ]["response"])){
            return null;
        }

        return $this->results[ $routeName ]["response"];
    }
}