SHELL := /bin/bash
.DEFAULT_GOAL := run
.PHONY: run extract

MAKEFILE_PATH := $(abspath $(lastword ${MAKEFILE_LIST}))
PROJECT_PATH := $(dir ${MAKEFILE_PATH})
PROJECT_NAME := $(notdir $(patsubst %/,%,$(dir ${PROJECT_PATH})))

export DOCKER_BUILDKIT ?= 1
export APP_NAME ?= ${PROJECT_NAME}
export XDEBUG_ENABLED ?= true
export XDEBUG_REMOTE_HOST ?= host.docker.internal
export XDEBUG_REMOTE_PORT ?= 10000
export XDEBUG_IDE_KEY ?= ${APP_NAME}_PHPSTORM
export MEMORY_LIMIT ?= 3M

run:
	docker build \
 	-t ${APP_NAME} \
 	 --target lambda \
 	 ${CURDIR}

	cat ${CURDIR}/lambda-payload.json | docker run \
    --name ${APP_NAME} \
    --rm \
    -i \
    -e APP_DEBUG \
    -e XDEBUG_ENABLED \
    -e XDEBUG_REMOTE_HOST \
    -e XDEBUG_REMOTE_PORT \
    -e XDEBUG_IDE_KEY \
    -e DOCKER_LAMBDA_USE_STDIN=1 \
    -v ${PROJECT_PATH}src:/var/task/src \
    ${APP_NAME}:latest \
	${FUNCTION}

extract:
	docker build \
    	-t ${APP_NAME}-build \
    	--target build \
    	${CURDIR}

	docker run \
    	--rm \
    	-it \
    	-v ${PROJECT_PATH}build:/var/task/build \
    	${APP_NAME}-build \
    	/bin/sh -c "set -ex && /root/.composer/vendor/bin/box compile"
