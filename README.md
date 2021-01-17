## What's this

This project is a PHP implementation of the [AWS Lambda Runtime Interface Client](https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html) (RIC).

This RIC is a standalone, executable PHAR file, containing its own Composer dependencies (e.g. Guzzle), and the source code of the implementation.

## Docker Image

This RIC is available as a Docker image in Docker Hub, [`gbmcarlos/php-runtime`](https://hub.docker.com/r/gbmcarlos/php-runtime).

The image is based on `scratch` ("an explicitly empty image"), and it contains 3 files:
- The Runtime Interface Client, as `/opt/bootstrap`.
- The Runtime Interface Emulator, as `/opt/aws-lambda-rie`
- The entrypoint script, as `/opt/lambda-entrypoint.hs`

More information about the Runtime Interface Emulator and the entrypoint script in the [Lambda and Docker section](#about-lambda-and-docker)

You can copy these files to your Docker image with a COPY instruction like

```dockerfile
COPY --from=gbmcarlos/php-runtime /opt /opt
```

## Using the Runtime

Once you have created a Docker image that contains this Runtime, if you want to test the Lambda function, you just need to run a container like this:
```shell
docker run -i 
  -e _HANDLER={your handler} 
  -p 8080 
  --entrypoint /opt/lambda-entrypoint.sh # This small script will decide whether to use the RIE or not
  {your image} 
```

You can then send invocation events to your function by sending POST requests to the port 8080:
```shell
curl -XPOST "http://localhost:8080/2015-03-31/functions/function/invocations" -d '{}'
```

### Environment Variables

In accordance to the Runtime Implementation specifications, this implementation expects the following environment variables to be present (from [AWS' documentation](https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-build)):
- `_HANDLER`: The location to the handler, from the function's configuration. The standard format is file.method, where file is the name of the file without an extension, and method is the name of a method or function that's defined in the file. (More information below)
- `LAMBDA_TASK_ROOT`: The directory that contains the function code.
- `AWS_LAMBDA_RUNTIME_API`: The host and port of the runtime API.

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
- handler value `src/app/function` will search for a function file `src/app/function.php`, relative to the function's source code root folder
- handler value `index.search` will search for a function file `index.php`, relative to the function's source code root folder, and pass the string `"search"` as the third parameter to the function

### Execution Cold Start

In accordance to AWS Lambda's best practices, you should do any initialization work inside the handler file, but outside the handler function, such as framework bootstrapping, or loading an SDK.

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

## About Lambda and Docker

### Without Docker support

To implement a custom Lambda Runtime, Lambda previously required the developer to provide the Runtime Interface Client (RIC) as a script packaged in the function's source code or in one of the function layers.
These script was then executed inside Lambda's own runtime environment.

When developing and testing locally, the developer needed to copy the source code and layers manually into a Docker image of AWS, which essentially simulated the Lambda environment.

### With Docker support

With the new support for Lambda functions packaged as Docker containers, Lambda doesn't execute the RIC directly, but instead runs a container with the developer's Docker image.

This means that the RIC, and the Runtime API are in separate environments. Specifically, when testing the RIC in a local environment, the Runtime API doesn't exist at all.

### Runtime Interface Emulator

With the RIC and the Runtime API being separate, the native execution environments (NodeJS, Python, Java, etc.), include a Runtime Interface Emulator (RIE). The RIE is a small application that simulates the Lambda API environment.

When the RIE is packaged inside the Runtime Docker image, the entrypoint of the Docker container needs to be a small script that will decide whether to use the emulator or not. If the environment variable `AWS_LAMBDA_RUNTIME_API` is present, it means that the container is being executed in a real Lambda environment (and therefore there is a Runtime API). If that environment variable is not present, the RIE needs to be executed.

When the RIE is executed, it will spin up a simulated Lambda API, and run the RIC (which will wait for invocation events). The RIE will then transform any requests to port 8080 to invocation events that the RIC can consume. 