<?php

namespace Runtime;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class Runtime {

    private string $baseUrl;
    private ClientInterface $client;
    private FunctionContainer $functionContainer;

    private $endpoints = [
        'nextInvocation' => '/runtime/invocation/next',
        'invocationResponse' => '/runtime/invocation/%s/response',
        'invocationError' => '/runtime/invocation/%s/error',
        'initError' => '/runtime/init/error'
    ];

    public function __construct(string $baseUrl, ClientInterface $client, FunctionContainer $functionContainer) {
        $this->baseUrl = $baseUrl;
        $this->client = $client;
        $this->functionContainer = $functionContainer;
    }

    public static function fromEnvVars() : self {

        return new self(
            trim(sprintf('http://%s/%s', getenv('AWS_LAMBDA_RUNTIME_API') ?? '', '2018-06-01')),
            new \GuzzleHttp\Client(),
            new \Runtime\FunctionContainer()
        );

    }

    public function start() {

        try {

            $function = $this->functionContainer->get(getenv('_HANDLER'));

        } catch (\Throwable $exception) {

            /*
             * After the initialization error is reported back to Lambda, this process will be killed
             */
            $this->initError(
                (new \ReflectionClass($exception))->getName(),
                $exception->getMessage()
            );

            exit;

        }

        /*
         * Whenever Lambda decides that this instance has processed enough, this process will be killed
         */
        for (; ;) {

            $invocation = $this->nextInvocation();

            try {

                $result = $function($invocation->getPayload());

            } catch (\Throwable $exception) {

                $payload = $invocation->getPayload();
                $errorType = (new \ReflectionClass($exception))->getName();
                $errorMessage = $exception->getMessage();
                $stackTrace = $exception->getTraceAsString();

                echo "Payload: " . json_encode($payload);
                echo "ErrorType: $errorType";
                echo "ErrorMessage: $errorMessage";
                echo "StackTrace: $stackTrace";

                $this->invocationError(
                    $invocation->getRequestId(),
                    $errorType,
                    $errorMessage
                );

                continue;

            }

            $this->invocationResponse($invocation->getRequestId(), $result);

        }

    }

    protected function nextInvocation() : Invocation {

        $response = $this->client->get(sprintf('%s%s', $this->baseUrl, $this->endpoints['nextInvocation']));

        $invocation = [
            'requestId' => $response->getHeader('Lambda-Runtime-Aws-Request-Id')[0],
            'invokedFunctionArn' => $response->getHeader('Lambda-Runtime-Invoked-Function-Arn')[0],
            'deadlineInMs' => $response->getHeader('Lambda-Runtime-Deadline-Ms')[0],
            'clientContext' => $response->getHeader('Lambda-Runtime-Client-Context')[0] ?? '',
            'identity' => $response->getHeader('Lambda-Runtime-Cognito-Identity')[0] ?? '',
            'traceId' => $response->getHeader('Lambda-Runtime-Trace-Id')[0] ?? '',
            'payload' => json_decode((string)$response->getBody(), true) ?? []
        ];

        $invocation = new Invocation(
            $invocation['requestId'],
            $invocation['invokedFunctionArn'],
            $invocation['deadlineInMs'],
            $invocation['clientContext'],
            $invocation['identity'],
            $invocation['traceId'],
            $invocation['payload']
        );

        return $invocation;

    }

    protected function invocationResponse(string $awsRequestId, array $response) : void {
        $this->client->post(
            sprintf('%s%s', $this->baseUrl, sprintf($this->endpoints['invocationResponse'], $awsRequestId)),
            [
                RequestOptions::JSON => $response
            ]
        );
    }

    protected function invocationError(string $awsRequestId, string $errorType, string $errorMessage) : void {
        $this->client->post(
            sprintf('%s%s', $this->baseUrl, sprintf($this->endpoints['invocationError'], $awsRequestId)),
            [
                RequestOptions::JSON => [
                    'errorType' => $errorType,
                    'errorMessage' => $errorMessage
                ],
                RequestOptions::HEADERS => [
                    'Lambda-Runtime-Function-Error-Type' => $errorType
                ]
            ]
        );
    }

    protected function initError(string $errorType, string $errorMessage) : void {
        $this->client->post(
            sprintf('%s%s', $this->baseUrl, $this->endpoints['initError']),
            [
                RequestOptions::JSON => [
                    'errorType' => $errorType,
                    'errorMessage' => $errorMessage
                ],
                RequestOptions::HEADERS => [
                    'Lambda-Runtime-Function-Error-Type' => $errorType
                ]
            ]
        );
    }

}