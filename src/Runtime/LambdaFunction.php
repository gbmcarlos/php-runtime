<?php

namespace Runtime;

abstract class LambdaFunction {

    abstract static public function getHandlerIdentifier() : string;
    
    public final function run(array $payload) : array {

        $this->writeln("function", json_encode($payload));

        return $this->doRun($payload);

    }

    abstract protected function doRun(array $payload) : array;

}