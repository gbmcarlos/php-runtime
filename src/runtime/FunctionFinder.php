<?php

namespace Runtime;

class FunctionFinder {

    public static function getHandler(string $directory, string $handler): array {

        list($filePath, $method) = self::parseHandler($handler);

        $handlerFile = $directory . '/' . $filePath . '.php';
        if (!is_file($handlerFile)) {
            throw new \Exception("Handler `$handlerFile` doesn't exist");
        }

        /** @noinspection PhpIncludeInspection */
        $handlerFunction = require $handlerFile;

        if (!is_callable($handlerFunction)) {
            throw new \Exception("Handler `$handlerFile` must return a \Closure");
        }

        return [
            $handlerFunction,
            $method
        ];

    }

    protected static function parseHandler(string $handler) : array {

        $segments = explode('.', $handler);

        if (count($segments) > 2) {
            throw new \Exception("Invalid handler value \"$handler\"");
        }

        return [
            $segments[0],
            $segments[1] ?? null
        ];

    }

}