all:
	echo "Default"
test:
	sh run_tests.sh
docker:
	docker-compose -f .docker/docker-compose.yml up -d --build
attach_php:
	docker exec -it narfex-php-fpm bash
