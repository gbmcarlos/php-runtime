# We are going to use this image as the base image at more than one step, so declare it beforehand
FROM gbmcarlos/php-base as php-base

# In this stage, we install the runtime's dependencies and bundle them with the source code
FROM php-base as build

WORKDIR /var/task

## First, install the tool we're gonna use to build the PHAR
RUN     composer global require \
            -v --no-suggest --no-interaction --no-ansi \
            humbug/box

## Now install the runtime's dependencies
COPY ./composer.* ./
RUN composer install \
        -v --no-autoloader --no-suggest --no-dev --no-interaction --no-ansi

## And now Box's config file
COPY ./box.json ./

## Then, the source code of the runtime
COPY ./src ./src

## And build the PHAR
RUN /root/.composer/vendor/bin/box compile

# In this state, start from scratch and just copy the final artifact from the previous stage
FROM php-base as bundle

COPY --from=build /var/task/build/bootstrap /opt/bootstrap

# This stage is for testing
## The first two COPY represent the final Lambda Layer, with binaries and bootstrap
## The second two COPY represent the function's content, with source code and config
FROM lambci/lambda:provided as lambda

## Base PHP Layer, it contains the binaries
COPY --from=php-base /opt /opt

## Override with the bootstrap we've built
COPY --from=bundle /opt/bootstrap /opt/bootstrap

## Add php config, to enable and configure Xdebug
COPY config/php.ini /var/task/php/conf.d/php.ini

## Add the demo functions, representing the function's source code
COPY ./demo /var/task/
