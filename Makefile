SHELL := /bin/bash
.DEFAULT_GOAL := build
.PHONY: build publish test

MAKEFILE_PATH := $(abspath $(lastword ${MAKEFILE_LIST}))
PROJECT_PATH := $(dir ${MAKEFILE_PATH})
PROJECT_NAME := $(notdir $(patsubst %/,%,$(dir ${PROJECT_PATH})))

export IMAGE_USER := gbmcarlos
export IMAGE_REPO := ${PROJECT_NAME}
export IMAGE_TAG ?= 2.0.0

export DOCKER_BUILDKIT ?= 1
export XDEBUG_ENABLED ?= true
export XDEBUG_REMOTE_HOST ?= host.docker.internal
export XDEBUG_REMOTE_PORT ?= 10000
export XDEBUG_IDE_KEY ?= ${APP_NAME}_PHPSTORM
export MEMORY_LIMIT ?= 3M

export _HANDLER ?= index

build:
	docker build \
		-t ${IMAGE_USER}/${IMAGE_REPO}:${IMAGE_TAG} \
		--target build \
		${CURDIR}

publish: build
	docker push ${IMAGE_USER}/${IMAGE_REPO}:${IMAGE_TAG}

test: build
	docker build \
 	-t ${IMAGE_USER}/${IMAGE_REPO}-test \
 	 --target test \
 	 ${CURDIR}/tests

	docker run \
	--name ${IMAGE_REPO}-test \
	--rm \
	-i \
	-e APP_DEBUG \
	-e XDEBUG_ENABLED \
	-e XDEBUG_REMOTE_HOST \
	-e XDEBUG_REMOTE_PORT \
	-e XDEBUG_IDE_KEY \
	-e _HANDLER \
	-p 8080:8080 \
	--entrypoint /opt/lambda-entrypoint.sh \
	${IMAGE_USER}/${IMAGE_REPO}-test