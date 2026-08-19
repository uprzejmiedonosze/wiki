# Docker (edge/Traefik) — the only supported path now, bare-metal (php -S /
# rsync to workflow.nieradka.net) has been dropped from this Makefile. See
# docker/Dockerfile, compose.yml, CLAUDE.md.

REMOTE_HOST ?= wiki@getdreamestate.com
REMOTE_DIR  ?= /opt/wiki

.PHONY: dev
dev:
	@docker compose -f compose.yml -f compose.dev.yml up --build
	# compose.dev.yml drops the `edge` external network requirement and
	# Traefik labels - reach it at http://localhost:8081/wiki/ instead of
	# the public hostname. WIKI_DOMAIN in .env is unused for this target.

.PHONY: validate
validate:
	@docker compose config > /dev/null

.PHONY: deploy
deploy:
	@echo "==> Pushing to origin"
	git push
	@echo "==> Deploying on $(REMOTE_HOST)"
	ssh $(REMOTE_HOST) 'cd $(REMOTE_DIR) && git pull && docker compose up -d --build --remove-orphans'

.PHONY: logs
logs:
	@ssh $(REMOTE_HOST) 'cd $(REMOTE_DIR) && docker compose logs -f --tail=200'

.PHONY: ps
ps:
	@ssh $(REMOTE_HOST) 'cd $(REMOTE_DIR) && docker compose ps'
