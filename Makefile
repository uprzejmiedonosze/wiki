.PHONY: clear-cache
clear-cache:
	@rm -rf data/cache/[a-z0-9]

.PHONY: dev
dev: clear-cache
	@echo "Starting DokuWiki development server with path mapping..."
	@php -S localhost:8080 router.php

.PHONY: deploy
deploy:
	@rsync -r bin lib conf inc vendor *php workflow.nieradka.net:/var/www/wiki.uprzejmiedonosze.net/wiki/

