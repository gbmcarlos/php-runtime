FROM gbmcarlos/php-base as php-base

# In this stage, we install the runtime's dependencies and bundle them with the source code
FROM php-base as build

WORKDIR /var/task

RUN composer global require humbug/box

COPY ./composer.* ./

RUN composer install \
        -v --no-autoloader --no-suggest --no-dev --no-interaction --no-ansi

COPY ./box.json ./

COPY ./src ./src

RUN /root/.composer/vendor/bin/box compile

FROM lambci/lambda:provided as lambda

## Base PHP Layer
## Project vendor Layer
COPY --from=php-base /opt /opt
COPY --from=build /var/task/build/bootstrap /opt/bootstrap

COPY config/php.ini /var/task/php/conf.d/php.ini

COPY ./src/app /var/task/app

ENV APP_NAME=localhost \
    XDEBUG_ENABLED=false \
    XDEBUG_REMOTE_HOST=host.docker.internal \
    XDEBUG_REMOTE_PORT=10000 \
    XDEBUG_IDE_KEY=${APP_NAME}"_PHPSTORM" \
    MEMORY_LIMIT="128M"
