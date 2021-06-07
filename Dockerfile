#####
#
# The build stage "compile":
# - installs `box`, a tool to create PHARs
# - copies composer's config files and installs the dependencies for the runtime implementation
# - copies box's config file
# - copies the source code
# - compiles the PHAR
#
# The build stage "build" just copies the PHAR into a clean scratch image
#
#####

ARG PHP_BASE_VERSION

### BUILD: install dependencies, copy the source code and create the PHAR
FROM gbmcarlos/php-base:${PHP_BASE_VERSION} as compile

WORKDIR /var/task

## First, install the tool we're gonna use to build the PHAR
RUN composer global require \
        -v --no-interaction --no-ansi \
        humbug/box:3.8.1

## Now install the runtime's dependencies
COPY ./composer.* ./
RUN composer install \
        -v --no-autoloader --no-suggest --no-dev --no-interaction --no-ansi

## And now Box's config file
COPY ./box.json ./

## Then, the source code of the runtime
COPY ./src ./src

## And build the PHAR
RUN /root/.config/composer/vendor/bin/box compile

## Download and install the Runtime Emulator
RUN curl -sLo /var/task/aws-lambda-rie \
        https://github.com/aws/aws-lambda-runtime-interface-emulator/releases/latest/download/aws-lambda-rie \
        && chmod +x /var/task/aws-lambda-rie

# In this state, start from scratch and just copy the final artifacts
FROM scratch as package

# The Runtime Client
COPY --from=compile /var/task/build/bootstrap /opt/bootstrap

# The Runtime Emulator
COPY --from=compile /var/task/aws-lambda-rie /opt/aws-lambda-rie

# The entrypoint
COPY src/lambda-entrypoint.sh /opt/lambda-entrypoint.sh