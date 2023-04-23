DOCKER_USERNAME ?= bdelespierre
APPLICATION_NAME ?= lcl-ledger-reader
GIT_HASH ?= $(shell git log --format="%h" -n 1)

build:
	docker build --tag ${DOCKER_USERNAME}/${APPLICATION_NAME}:${GIT_HASH} .
