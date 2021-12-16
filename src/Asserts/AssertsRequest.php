<?php

namespace TesterPhp\Asserts;

class AssertsRequest {

    private $assertsValues;
    private $responseObject;

    /**
     * @throws \Exception
     */
    public function __construct(object $responseObject, object $assertsValues) {
        $this->assertsValues = $assertsValues;
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
        $description = ($this->assertsValues->description ?: "StatusCode ") . "get $result, attempted $attempted";
        switch ($typeComparison) {
            case "notEqual":
                $this->assert($attempted !== $result, $description);
                break;
            case "equal":
            default:
                $this->assert($attempted === $result, $description);
        }
    }

    /**
     * @throws \Exception
     */
    private function assert(bool $condition, string $description = null) {
        if (false === assert($condition, $description)) {
            throw new \Exception("TEST FAILED : $description");
        } else {
            echo "Test success : $description\n";
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
        $description = $this->assertsValues->description ?: "Header ";
        $this->assert(
            isset($headerResponse), "$description found {$header->name}");
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaderType(object $header) {
        $headerResponse = $this->responseObject->headers[ $header->name ];
        $this->assertsType($header->type, $header->name, $headerResponse[0]);
    }

    /**
     * @throws \Exception
     */
    private function assertsType(string $type, string $name, $response) {
        $description = ($this->assertsValues->description ?: "Type ") . "{$name} is ".gettype($response).", attempted $type";
        switch ($type) {
            case "url":
                $this->assert(filter_var($response, FILTER_VALIDATE_URL), $description);
                break;
            case "int":
                $this->assert(is_numeric($response), $description);
                break;
            case "bool":
                $this->assert(is_bool($response), $description);
                break;
            case "array":
                $this->assert(is_array($response), $description);
                break;
            case "object":
                $this->assert(is_object($response), $description);
                break;
            case "string":
            default:
                $this->assert(is_string($response), $description);
        }
    }

    /**
     * @throws \Exception
     */
    private function assertsHeaderEqual(object $header) {
        $headerResponse = $this->responseObject->headers[ $header->name ][0];
        $description = ($this->assertsValues->description ?: "Header ") . "{$header->name} is ".gettype($headerResponse).", attempted {$header->value}";
        $this->assert($headerResponse === $header->value, $description);
    }

    /**
     * @throws \Exception
     */
    private function assertsSchema(array $schemas) {
        foreach ($schemas as $schema) {
            switch ($schema->type) {
                case "notNull":
                    $this->assert(
                        property_exists($this->responseObject, "response") && $this->responseObject->response !== null,
                        ($this->assertsValues->description ?: "Response ") . "attempted {$schema->type}");
                    break;
                case "null":
                    $this->assert(
                        property_exists($this->responseObject, "response") && $this->responseObject->response === null,
                        ($this->assertsValues->description ?: "Response ") . "attempted {$schema->type}");
                    break;
                case "notFound":
                    $this->assert(
                        !$this->checkType($this->responseObject->response, $schema->schema),
                        ($this->assertsValues->description ?: "Schema ") . json_encode($schema->schema)." attempted {$schema->type}");
                    break;
                case "found":
                    $this->assert(
                        $this->checkType($this->responseObject->response, $schema->schema),
                        ($this->assertsValues->description ?: "Schema ") . json_encode($schema->schema)." attempted {$schema->type}");
                    break;
                case "equal":
                    $this->assert(
                        $this->checkEqual($this->responseObject->response, $schema->schema),
                        ($this->assertsValues->description ?: "Schema ") . json_encode($schema->schema)." attempted {$schema->type}");
                    break;
                case "notEqual":
                    $this->assert(
                        !$this->checkEqual($this->responseObject->response, $schema->schema),
                        ($this->assertsValues->description ?: "Schema ") . json_encode($schema->schema)." attempted {$schema->type}");
                    break;
                default:
                    $this->notFound($this->assertsValues->description ?: "Schema ", "{$schema->type} not found !!");
            }
        }
    }

    private function checkType(array $response, $schema): bool {
        foreach ($schema as $k => $val) {
            if (isset($response[ $k ])) {
                if (is_object($val)) {
                    return $this->checkType($response[ $k ], $val);
                } else {
                    try {
                        $this->assertsType($val, $k, $response[ $k ]);
                    } catch (\Exception $e) {
                        //
                    } finally {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function checkEqual(array $response, $schema): bool {
        foreach ($schema as $k => $val) {
            if (isset($response[ $k ])) {
                if (is_object($val) || is_array($val)) {
                    return $this->checkEqual($response[ $k ], (object) $val);
                } else {
                    return (string) $val === (string) $response[ $k ];
                }
            }
        }
        return false;
    }

    private function notFound(string $description, string $msg){
        echo "Test WARNING : $description $msg\n";
    }
}