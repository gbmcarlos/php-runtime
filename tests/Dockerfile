FROM gbmcarlos/php-base:2.0.0 as test

# Copy the Runtime Interface Client, Runtime Interface Emulator and the entrypoint
COPY --from=gbmcarlos/php-runtime:2.0.0 /opt /opt

## Add php config, to enable and configure Xdebug
COPY config/php.ini /var/task/php/conf.d/php.ini

## Add the demo functions, representing the function's source code
COPY src /var/task/