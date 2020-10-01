<?php

namespace Runtime;

class Invocation {

    protected string $requestId;
    protected string$invokedFunctionArn;
    protected int $deadlineInMs;
    protected string $clientContext;
    protected string $identity;
    protected string $traceId;
    protected array $payload;
    protected array $context = [];

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
    public function __construct(string $requestId, string $invokedFunctionArn, int $deadlineInMs, string $clientContext, string $identity, string $traceId, array $payload) {

        $this->requestId = $requestId;
        $this->invokedFunctionArn = $invokedFunctionArn;
        $this->deadlineInMs = $deadlineInMs;
        $this->clientContext = $clientContext;
        $this->identity = $identity;
        $this->traceId = $traceId;
        $this->payload = $payload;

        $this->buildContext();

    }

    protected function buildContext() {

        $arn = $this->getInvokedFunctionArn();
        $segments = explode(':', $arn);
        $functionName = $segments[6];
        $functionVersion = $segments[7] ?? null;
        $this->context['function_name'] = $functionName;
        $this->context['function_version'] = $functionVersion;
        $this->context['invoked_function_arn'] = $arn;

        $this->context['aws_request_id'] = $this->getRequestId();

    }

    public function getRequestId() {
        return $this->requestId;
    }

    public function getInvokedFunctionArn() : string {
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
        return $this->context;
    }

}