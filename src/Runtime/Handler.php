<?php

namespace Runtime;

class Handler {

    public static function handle() {

        $runtime = new Runtime('2018-06-01');

        $handlerIdentifier = getenv('_HANDLER');

        for (; ;) {

            $invocation = $runtime->nextInvocation();

            try {

                $result = null;

                // Execute the function

            } catch (\Throwable $exception) {

                $payload = $invocation->getPayload();
                $errorType = (new \ReflectionClass($exception))->getName();
                $errorMessage = $exception->getMessage();
                $stackTrace = $exception->getTraceAsString();

                echo "Payload: " . json_encode($payload);
                echo "ErrorType: $errorType";
                echo "ErrorMessage: $errorMessage";
                echo "StackTrace: $stackTrace";

                $runtime->invocationError(
                    $invocation->getRequestId(),
                    $errorType,
                    $errorMessage
                );

                continue;

            }

            $runtime->invocationResponse($invocation->getRequestId(), $result);

        }

    }

}