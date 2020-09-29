<?php

namespace Runtime;

class FunctionContainer {

    private $directory;

    public function __construct(?string $directory = null) {
        $this->directory = $directory ?: getenv('LAMBDA_TASK_ROOT');
    }

    public function get(string $id) : \Closure {

        $handlerFile = $this->directory . '/' . $id . '.php';
        if (! is_file($handlerFile)) {
            throw new \Exception("Handler `$handlerFile` doesn't exist");
        }

        /** @noinspection PhpIncludeInspection */
        $handler = require $handlerFile;

        if (!is_callable($handler)) {
            throw new \Exception("Handler `$handlerFile` must return a \Closure");
        }

        return $handler;

    }

}