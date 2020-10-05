### What's this
This project is an implementation of AWS Lambda Runtime, written in PHP for PHP

Based on a [basic PHP environment](https://github.com/gbmcarlos/php-stack#php-base), it installs Guzzle, copies the Runtime implementation, and packages the whole thing as a compressed executable PHAR file named `bootstrap` of around 200 kB.

### Function Handler

This PHP Runtime aims at implementing an interface similar to the existing runtimes supported in Lambda.

Based on the `handler` configuration value of the function, the runtime will search for the file to execute, and pass the payload to it.

The function file must be a PHP file that returns a function. This function will be called with 3 parameters: the payload object, a context object, and an optional method identifier, based on the format of the `handler` value.

For example:

```php
<?php

return function(array $payload, array $context, ?string $method) {
    return $payload;
};
```

The format of the function's handler value consists of two parts:
- a path to the function PHP file, without the `.php` extension
- optionally, a dot (`.`) and an extra identifier, which will be passed to the function as the third parameter `$method`

For example:
- handler value `index` will search for a function file `index.php`, relative to the function's source code root folder
- handler value `src/app/function` will search for a function file `src/app/function`, relative to the function's source code root folder
- handler value `index.search` will search for a function file `index.php`, relative to the function's source code root folder, and pass the string `"search"` as the third parameter to the function

### Execution Cold Start

In accordance to AWS Lambda's best practices, you should do any initialization work outside the handler function, such as framework bootstrapping, or loading an SDK.

This Runtime supports this, by requiring the file only once, and reusing the same Closure for subsequent invocations.

### Context object

The context object passed as the second parameter to the handler function contains the following properties
- `function_name` – The name of the Lambda function.
- `function_version` – The version of the function.
- `invoked_function_arn` – The Amazon Resource Name (ARN) that's used to invoke the function. Indicates if the invoker specified a version number or alias.
- `memory_limit_in_mb` – The amount of memory that's allocated for the function.
- `aws_request_id` – The identifier of the invocation request.
- `log_group_name` – The log group for the function.
- `log_stream_name` – The log stream for the function instance.
