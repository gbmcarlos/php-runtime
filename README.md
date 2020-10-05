### What's this
This project is an AWS Serverless Application that provides support for PHP 7.4 Lambda functions.

This Application is [published to the AWS Serverless Application Repository](https://serverlessrepo.aws.amazon.com/applications/eu-west-2/613351270255/php-74-runtime), under the name `php-74-runtime` so it's available for others to find and deploy.

### PHP Runtime

By deploying this Serverless Application, a Lambda Layer and an SSM Parameter will be created in your account.

The Lambda Layer contains the binaries required to execute PHP, and a full implementation of AWS Lambda Runtime.

The SSM Parameter contains the ARN of the Layer.

### Using the PHP Runtime

To use this PHP Runtime, configure a Lambda Function to use a custom runtime, and add the Layer to it.

If you use an *Infrastructure as Code* tool, such as CloudFormation, the SSM Parameter can be used to get the ARN of the layer, instead of hardcoding it.

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
