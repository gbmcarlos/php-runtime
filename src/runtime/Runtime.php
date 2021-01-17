<?php

namespace Runtime;

class Runtime {

    protected ?\Closure $handlerFunction = null;
    protected ?string $handlerMethod = null;

    protected LambdaAPI $lambdaApi;

    public function __construct(LambdaAPI $lambdaApi) {
        $this->lambdaApi = $lambdaApi;
    }

    public static function fromEnvVars() : self {

        return new self(
            new LambdaAPI(
                trim(sprintf('http://%s/%s', getenv('AWS_LAMBDA_RUNTIME_API') ?? '', '2018-06-01')),
                new \GuzzleHttp\Client()
            )
        );

    }

    public function run() {

        $this->initHandler();

        for (; ;) {

            $invocation = $this->lambdaApi->getNextInvocation();

            $this->processInvocation(
                $invocation
            );

        }

    }

    /*
     * Resolve the handler and get the Closure
     * If there is any problem, report it and exit
     */
    protected function initHandler() {

        try {

            list($function, $method) = HandlerFactory::getHandler(
                getenv('LAMBDA_TASK_ROOT'),
                getenv('_HANDLER', 'index')
            );

            $this->handlerFunction = $function;
            $this->handlerMethod = $method;

        } catch (\Throwable $exception) {

            /*
             * After the initialization error is reported back to Lambda, this process will be killed
             */
            $this->lambdaApi->initError(
                (new \ReflectionClass($exception))->getName(),
                $exception->getMessage()
            );

            exit;

        }

    }

    /*
     * Given an invocation, use the resolved handler to process it
     * If there is any error, report it, and go back to the loop for the next invocation
     */
    protected function processInvocation(Invocation $invocation) {

        try {

            $function = $this->handlerFunction;

            $result = $function(
                $invocation->getPayload(),
                $invocation->getContext(),
                $this->handlerMethod
            );

        } catch (\Throwable $exception) {

            $this->reportError($invocation, $exception);
            return;

        }

        $this->lambdaApi->invocationResponse($invocation->getRequestId(), $result);

    }

    protected function reportError(Invocation $invocation, \Throwable $exception) {

        $payload = $invocation->getPayload();
        $errorType = (new \ReflectionClass($exception))->getName();
        $errorMessage = $exception->getMessage();
        $stackTrace = $exception->getTraceAsString();

        echo "Payload: " . json_encode($payload) . "\n";
        echo "ErrorType: $errorType\n";
        echo "ErrorMessage: $errorMessage\n";
        echo "StackTrace: $stackTrace\n";

        $this->lambdaApi->invocationError(
            $invocation->getRequestId(),
            $errorType,
            $errorMessage
        );

    }

}