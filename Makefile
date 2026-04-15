.PHONY: up down build rebuild logs shell manager

## Start the container (builds the image first if it doesn't exist)
up:
	docker compose up -d

## Stop and remove the container
down:
	docker compose down

## Build (or rebuild) the image
build:
	docker compose build

## Force a full image rebuild from scratch, ignoring the layer cache
rebuild:
	docker compose build --no-cache

## Tail the application logs
logs:
	docker compose logs -f

## Open a shell inside the running container
shell:
	docker compose exec app bash

## Launch the interactive Pi-hole manager (container must be running)
manager:
	docker compose exec app php artisan pausepi:manager
