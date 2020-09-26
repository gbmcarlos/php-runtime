<?php

namespace Runtime;

class DefaultLambdaFunction extends LambdaFunction {

    static public function getHandlerIdentifier(): string {
        return 'default';
    }

    public function doRun(array $payload) : array {
        
        $this->writeln("function", json_encode($payload));
        return $payload;
        
    }

}