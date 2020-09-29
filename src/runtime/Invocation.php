<?php

namespace Runtime;

class Invocation {

    protected $requestId;
    protected $invokedFunctionArn;
    protected $deadlineInMs;
    protected $clientContext;
    protected $identity;
    protected $traceId;
    protected $payload;

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
    }

    public function getRequestId() {
        return $this->requestId;
    }

    public function getInvokedFunctionArn() {
        return $this->invokedFunctionArn;
    }

    public function getDeadlineInMs() {
        return $this->deadlineInMs;
    }

    public function getClientContext() {
        return $this->clientContext;
    }

    public function getIdentity() {
        return $this->identity;
    }

    public function getTraceId() {
        return $this->traceId;
    }

    public function getPayload() {
        return $this->payload;
    }

}