SHELL := /bin/bash
.DEFAULT_GOAL := run
.PHONY: build run extract publish

MAKEFILE_PATH := $(abspath $(lastword ${MAKEFILE_LIST}))
PROJECT_PATH := $(dir ${MAKEFILE_PATH})
PROJECT_NAME := $(notdir $(patsubst %/,%,$(dir ${PROJECT_PATH})))

export IMAGE_USER := gbmcarlos
export IMAGE_TAG := latest

export DOCKER_BUILDKIT ?= 1
export APP_NAME ?= ${PROJECT_NAME}
export XDEBUG_ENABLED ?= true
export XDEBUG_REMOTE_HOST ?= host.docker.internal
export XDEBUG_REMOTE_PORT ?= 10000
export XDEBUG_IDE_KEY ?= ${APP_NAME}_PHPSTORM
export MEMORY_LIMIT ?= 3M

run:
	docker build \
 	-t ${IMAGE_USER}/${APP_NAME}-lambda:${IMAGE_TAG} \
 	 --target lambda \
 	 ${CURDIR}

	cat ${CURDIR}/lambda-payload.json | docker run \
    --name ${APP_NAME}-lambda \
    --rm \
    -i \
    -e APP_DEBUG \
    -e XDEBUG_ENABLED \
    -e XDEBUG_REMOTE_HOST \
    -e XDEBUG_REMOTE_PORT \
    -e XDEBUG_IDE_KEY \
    -e DOCKER_LAMBDA_USE_STDIN=1 \
    ${IMAGE_USER}/${APP_NAME}-lambda:${IMAGE_TAG} \
	${FUNCTION}

build:
	docker build \
		-t ${IMAGE_USER}/${APP_NAME}:${IMAGE_TAG} \
		--target build \
		${CURDIR}

extract: build
	docker run \
    	--rm \
    	-it \
    	-v ${PROJECT_PATH}build:/var/task/build \
    	${APP_NAME} \
    	/bin/sh -c "set -ex && /root/.composer/vendor/bin/box compile"

publish: build
	docker push ${IMAGE_USER}/${APP_NAME}
