FROM gbmcarlos/stacks:php-base as build

WORKDIR /var/task

COPY ./composer.* ./

RUN composer install \
        -v --no-autoloader --no-suggest --no-dev --no-interaction --no-ansi

COPY ./src ./src

RUN composer dump-autoload -v --classmap-authoritative --no-dev --no-interaction --no-ansi

FROM gbmcarlos/stacks:php-base as bundle

WORKDIR /var/task

RUN composer global require humbug/box

COPY --from=build /var/task /var/task

COPY ./box.json ./

RUN /root/.composer/vendor/bin/box compile

FROM gbmcarlos/stacks:php as php-lambda

FROM lambci/lambda:provided as lambda

## Base PHP Layer
## Project vendor Layer
COPY --from=php-lambda /opt /opt/
COPY --from=bundle /var/task/build/php-runtime.phar /opt/php-runtime.phar

ENV APP_NAME=localhost \
    XDEBUG_ENABLED=false \
    XDEBUG_REMOTE_HOST=host.docker.internal \
    XDEBUG_REMOTE_PORT=10000 \
    XDEBUG_IDE_KEY=${APP_NAME}"_PHPSTORM" \
    MEMORY_LIMIT="128M"
