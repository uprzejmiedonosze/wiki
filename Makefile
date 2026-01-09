.PHONY: clear-cache
clear-cache:
	@rm -rf data/cache/[a-z0-9]

.PHONY: start
start: clear-cache
	@php -S localhost:8080

.PHONY: deploy
deploy:
	@rsync -r bin lib conf inc vendor *php workflow:/var/www/wiki.uprzejmiedonosze.net/wiki/

