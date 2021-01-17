<?php

namespace Runtime;

class Invocation {

    protected string $requestId;
    protected ?string $invokedFunctionArn;
    protected int $deadlineInMs;
    protected string $clientContext;
    protected string $identity;
    protected string $traceId;
    protected array $payload;

    /**
     * Invocation constructor.
     * @param $requestId
     * @param $invokedFunctionArn
     * @param $deadlineInMs
     * @param $clientContext
     * @param $identity
     * @param $traceId
     * @param $payload
     */
    public function __construct(string $requestId, ?string $invokedFunctionArn, int $deadlineInMs, string $clientContext, string $identity, string $traceId, array $payload) {

        $this->requestId = $requestId;
        $this->invokedFunctionArn = $invokedFunctionArn;
        $this->deadlineInMs = $deadlineInMs;
        $this->clientContext = $clientContext;
        $this->identity = $identity;
        $this->traceId = $traceId;
        $this->payload = $payload;

    }

    public function getRequestId() {
        return $this->requestId;
    }

    public function getInvokedFunctionArn() : ?string {
        return $this->invokedFunctionArn;
    }

    public function getDeadlineInMs() : int {
        return $this->deadlineInMs;
    }

    public function getClientContext() : string {
        return $this->clientContext;
    }

    public function getIdentity() : string {
        return $this->identity;
    }

    public function getTraceId() : string {
        return $this->traceId;
    }

    public function getPayload() : array {
        return $this->payload;
    }

    public function getContext() : array {

        return [
            'function_name' => getenv('AWS_LAMBDA_FUNCTION_NAME'),
            'function_version' => getenv('AWS_LAMBDA_FUNCTION_VERSION'),
            'invoked_function_arn' => $this->getInvokedFunctionArn(),
            'memory_limit_in_mb' => (int) getenv('AWS_LAMBDA_FUNCTION_MEMORY_SIZE'),
            'aws_request_id' => $this->getRequestId(),
            'log_group_name' => getenv('AWS_LAMBDA_LOG_GROUP_NAME'),
            'log_stream_name' => getenv('AWS_LAMBDA_LOG_STREAM_NAME'),
        ];

    }

}