<?php

namespace Runtime;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class LambdaAPI {

    private string $baseUrl;
    private ClientInterface $client;

    private $endpoints = [
        'nextInvocation' => '/runtime/invocation/next',
        'invocationResponse' => '/runtime/invocation/%s/response',
        'invocationError' => '/runtime/invocation/%s/error',
        'initError' => '/runtime/init/error'
    ];

    public function __construct(string $baseUrl, ClientInterface $client) {
        $this->baseUrl = $baseUrl;
        $this->client = $client;
    }

    public function getNextInvocation() : Invocation {

        $response = $this->client->get(sprintf('%s%s', $this->baseUrl, $this->endpoints['nextInvocation']));

        $invocation = [
            'requestId' => $response->getHeader('Lambda-Runtime-Aws-Request-Id')[0],
            'invokedFunctionArn' => $response->getHeader('Lambda-Runtime-Invoked-Function-Arn')[0] ?? null,
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

    public function invocationResponse(string $awsRequestId, array $response) : void {
        $this->client->post(
            sprintf('%s%s', $this->baseUrl, sprintf($this->endpoints['invocationResponse'], $awsRequestId)),
            [
                RequestOptions::JSON => $response
            ]
        );
    }

    public function invocationError(string $awsRequestId, string $errorType, string $errorMessage) : void {
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

    public function initError(string $errorType, string $errorMessage) : void {

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