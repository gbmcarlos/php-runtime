#!/usr/bin/env bash

# If the Runtime API is not available, execute the emulator
if [ -z "${AWS_LAMBDA_RUNTIME_API}" ]; then
  exec /opt/aws-lambda-rie /opt/bootstrap ${_HANDLER}
else
  exec /opt/bootstrap
fi
