.PHONY: clear-cache
clear-cache:
	@rm -rf data/cache/[a-z0-9]

.PHONY:
start: clear-cache
	@php -S localhost:8000
