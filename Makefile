SHELL := /bin/bash
.DEFAULT_GOAL := logs
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

	docker rm -f ${APP_NAME} || true

	docker run \
    --name ${APP_NAME} \
    -it \
    -e APP_DEBUG \
    -e XDEBUG_ENABLED \
    -e XDEBUG_REMOTE_HOST \
    -e XDEBUG_REMOTE_PORT \
    -e XDEBUG_IDE_KEY \
    -v ${PROJECT_PATH}src:/var/task/src \
    -v ${PROJECT_PATH}vendor:/opt/runtime/vendor \
    ${APP_NAME}:latest

extract: bundle
	docker build \
    	-t ${APP_NAME}-bundle \
    	--target bundle \
    	${CURDIR}

	docker run \
    	--rm \
    	-v ${PROJECT_PATH}build:/var/task/build \
    	${APP_NAME}-bundle \
    	/bin/sh -c "set -ex && /root/.composer/vendor/bin/box compile"

