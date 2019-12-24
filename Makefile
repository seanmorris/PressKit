#!make

PROJECT ?=presskit
REPO    ?=seanmorris
MAKEDIR :=$(dir $(abspath $(firstword $(MAKEFILE_LIST))))

-include .env
-include .env.${TARGET}

-include vendor/seanmorris/ids/Makefile

init:
	@ docker run --rm \
		-v $$PWD:/app \
		-v $${COMPOSER_HOME:-$$HOME/.composer}:/tmp \
		composer install
