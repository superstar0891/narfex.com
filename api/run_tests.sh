#!/usr/bin/env bash

cd .docker
docker-compose -f tests.docker-compose.yml up -d
docker exec -it tests-narfex-php-fpm sh "utils/container_tests.sh"
