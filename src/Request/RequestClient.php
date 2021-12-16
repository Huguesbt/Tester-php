<?php

namespace TesterPhp\Request;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestClient {

    /** @var HttpClient */
    private $client;
    private $url;
    private $method;
    private $headers;
    private $body;
    private $json;
    /** @var ResponseInterface */
    private $response;

    public function __construct() {
        $this->client = HttpClient::create([
            "verify_host" => false,
            "verify_peer" => false,
       ]);
    }

    public function setUrl(string $url) {
        $this->url = $url;
    }

    public function setMethod(string $method) {
        $this->method = $method;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
    }

    public function setBody(string $body) {
        $this->body = $body;
    }

    public function setJson(array $body) {
        $this->json = $body;
    }

    /**
     * @throws \Exception
     */
    public function send() {
        $this->checkUrl();
        $this->checkMethod();
        $this->checkBody();
        try {
            $this->response = $this->client->request(
                $this->method,
                $this->url,
                [
                    'headers' => $this->headers,
                    $this->body ? "body" : "json" => $this->body ?: $this->json,
                ]
            );
//            var_dump($this->response->getInfo("url"));
//            var_dump($this->response->getStatusCode());
//            var_dump($this->response->getHeaders());
//            var_dump($this->response->getContent());
        } catch (TransportExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function checkUrl() {
        if ($this->url == "") {
            throw new \Exception("Url is empty !");
        }
    }

    private function checkMethod() {
        $this->method = $this->method ?: "GET";
    }

    /**
     * @throws \Exception
     */
    private function checkBody() {
        if ($this->body != "" && $this->json != "") {
            throw new \Exception("Raw body and json body are setted !! Only once must it !");
        }
    }

    public function getResponseInfo(string $field = null) {
        return $this->response->getInfo($field);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getResponsestatusCode(): int {
        return $this->response->getStatusCode();
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function getRawBody(): string {
        return ($respContent = $this->response->getContent(false)) !== null
            ? $respContent
            : "";
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function getJsonBody(): ?array {
        return ($respContent = $this->response->getContent(false)) !== null
            ? json_decode($respContent, true)
            : null;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function getJsonBodyObject(): object {
        return ($respContent = $this->response->getContent(false)) !== null
            ? json_decode($respContent)
            : (object)[];
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function getResponseHeaders(): array {
        return $this->response->getHeaders();
    }
}