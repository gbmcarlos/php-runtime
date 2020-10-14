SHELL := /bin/bash
.DEFAULT_GOAL := run
.PHONY: run build extract package publish

MAKEFILE_PATH := $(abspath $(lastword ${MAKEFILE_LIST}))
PROJECT_PATH := $(dir ${MAKEFILE_PATH})
PROJECT_NAME := $(notdir $(patsubst %/,%,$(dir ${PROJECT_PATH})))

export IMAGE_USER := gbmcarlos
export IMAGE_REPO := ${PROJECT_NAME}
export IMAGE_TAG := latest

export DOCKER_BUILDKIT ?= 1
export XDEBUG_ENABLED ?= true
export XDEBUG_REMOTE_HOST ?= host.docker.internal
export XDEBUG_REMOTE_PORT ?= 10000
export XDEBUG_IDE_KEY ?= ${APP_NAME}_PHPSTORM
export MEMORY_LIMIT ?= 3M

run:
	docker build \
 	-t ${IMAGE_USER}/${IMAGE_REPO}-lambda \
 	 --target lambda \
 	 ${CURDIR}

	cat ${CURDIR}/lambda-payload.json | docker run \
        --name ${IMAGE_REPO}-lambda \
        --rm \
        -i \
        -e APP_DEBUG \
        -e XDEBUG_ENABLED \
        -e XDEBUG_REMOTE_HOST \
        -e XDEBUG_REMOTE_PORT \
        -e XDEBUG_IDE_KEY \
        -e DOCKER_LAMBDA_USE_STDIN=1 \
        ${IMAGE_USER}/${IMAGE_REPO}-lambda \
    	${HANDLER}

build:
	docker build \
		-t ${IMAGE_USER}/${IMAGE_REPO} \
		--target bundle \
		${CURDIR}

extract: build
	docker run \
	--rm \
	-v ${CURDIR}:/var/mount \
	--entrypoint /bin/sh \
	${IMAGE_USER}/${IMAGE_REPO} \
	-c "cd /opt; zip -r /var/mount/php-74-runtime.zip ./bin ./bootstrap ./bref"

package: extract
	aws cloudformation package \
     	--template-file sam.yaml \
     	--s3-bucket ${SAM_ARTIFACTS_BUCKET} \
     	--output-template-file ${CURDIR}/sam-output.yaml

publish: package
	docker tag ${IMAGE_USER}/${IMAGE_REPO} ${IMAGE_USER}/${IMAGE_REPO}:latest
	docker tag ${IMAGE_USER}/${IMAGE_REPO} ${IMAGE_USER}/${IMAGE_REPO}:${IMAGE_TAG}
	docker push ${IMAGE_USER}/${IMAGE_REPO}