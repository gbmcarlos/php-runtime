<?php

require __DIR__ . '/../vendor/autoload.php';

$runtime = \Runtime\Runtime::fromEnvVars();

$runtime->start();
