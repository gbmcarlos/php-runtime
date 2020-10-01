<?php

namespace Runtime;

class HandlerFactory {

    public static function getHandler(string $directory, string $handlerId): array {

        list($filePath, $method) = self::parseHandlerId($handlerId);

        $function = self::loadFunctionFile($directory, $filePath);

        return [
            $function,
            $method
        ];

    }

    protected static function parseHandlerId(string $handlerId) : array {

        $segments = explode('.', $handlerId);

        if (count($segments) > 2) {
            throw new \Exception("Invalid handler value \"$handlerId\"");
        }

        return [
            $segments[0],
            $segments[1] ?? null
        ];

    }

    protected static function loadFunctionFile(string $directory, string $filePath) : \Closure {

        $handlerFile = $directory . '/' . $filePath . '.php';
        if (!is_file($handlerFile)) {
            throw new \Exception("Handler `$handlerFile` doesn't exist");
        }

        /** @noinspection PhpIncludeInspection */
        $handlerFunction = require $handlerFile;

        if (!$handlerFunction instanceof \Closure) {
            throw new \Exception("Handler `$handlerFile` must return a \Closure");
        }

        return $handlerFunction;

    }

}