<?php

namespace App\Asserts;

class AssertsRequest {

    private $assertsValues;
    private $responseObject;

    /**
     * @throws \Exception
     */
    public function __construct(object $responseObject, object $assertsValues) {
        $this->assertsValues  = $assertsValues;
        property_exists($this->assertsValues, 'description') ?: $this->assertsValues->description = "";
        $this->responseObject = $responseObject;

        echo "Run test for {$responseObject->method} {$responseObject->url}\n";
        $this->run();
    }

    /**
     * @throws \Exception
     */
    private function run() {
        if (isset($this->assertsValues->status)) {
            $this->assertsStatusCode(
                $this->assertsValues->status->code,
                $this->responseObject->status,
                $this->assertsValues->status->type
            );
        }
        if (isset($this->assertsValues->headers)) {
            $this->assertsHeaders($this->assertsValues->headers);
        }
        if (isset($this->assertsValues->schema)) {
            $this->assertsSchema($this->assertsValues->schema);
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsStatusCode(int $attempted, int $result, string $typeComparison) {
        switch ($typeComparison) {
            case "notEqual":
                $this->assert(
                    $attempted !== $result, ($this->assertsValues->description ?: "statusCode") . " get $result, attempted
                $attempted");
                break;
            case "equal":
            default:
                $this->assert(
                    $attempted === $result, ($this->assertsValues->description ?: "statusCode") . " get $result, attempted $attempted");
        }
    }

    /**
     * @throws \Exception
     */
    private function assert(bool $condition, string $description = null) {
        if (false === assert($condition, $description)) {
            throw new \Exception("TEST FAILED $description");
        } else {
            echo "Test success $description\n";
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaders(array $headers) {
        foreach ($headers as $header) {
            $this->assertsHeaderFound($header);
            if (isset($header->type)) $this->assertsHeaderType($header);
            if (isset($header->value)) $this->assertsHeaderEqual($header);
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaderFound(object $header) {
        $headerResponse = $this->responseObject->headers[ $header->name ];
        $this->assert(
            isset($headerResponse),
            $this->assertsValues->description ?: "found header {$header->name}");
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaderType(object $header) {
        $headerResponse = $this->responseObject->headers[ $header->name ];
        switch ($header->type) {
            case"url":
                $this->assert(
                    isset($headerResponse) && filter_var($headerResponse[0], FILTER_VALIDATE_URL),
                    $this->assertsValues->description ?: "type header {$header->name} is url");
                break;
            case "string":
            default:
                $this->assert(
                    isset($headerResponse) && is_string($headerResponse[0]),
                    $this->assertsValues->description ?: "type header {$header->name} is string");
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaderEqual(object $header) {
        $headerResponse = $this->responseObject->headers[ $header->name ];
        $this->assert(
            isset($headerResponse) && $headerResponse[0] === $header->value,
            $this->assertsValues->description ?: "found header {$header->name}");
    }

    /**
     * @throws \Exception
     */
    private function assertsSchema(array $schemas) {
        foreach ($schemas as $schema) {
            switch ($schema->type){
                case "notNull":
                    $this->assert(
                        property_exists($this->responseObject, "response") && $this->responseObject->response !== null,
                        $this->assertsValues->description ?: " get null, attempted not null");
                break;
            }
//            $this->assertsSchemaFound($schema);
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsSchemaFound(object $schema) {
//        $headerResponse = $this->responseObject->response[ $schema ];
//        $this->assert(
//            isset($headerResponse),
//            $this->assertsValues->description ?: "found header {$header->name}");
    }
}