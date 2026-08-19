#!/bin/sh
set -eu

# data/ and conf/ are named volumes (see compose.yml) so wiki content and
# ACL edits survive `docker compose up --build`. Volumes mount empty, so on
# first boot only, seed them from the copies baked into the image at
# /var/www/seed-{data,conf} (see Dockerfile). Later boots see a non-empty
# dir and skip this - which also means a real bind-mounted data/ (see
# WIKI_DATA_SOURCE in compose.yml) is never touched here, since it's never
# empty. chown only runs right after seeding a *fresh* volume, not on every
# boot - a previously-seeded named volume already has correct ownership
# from its own first boot, and re-chowning unconditionally on every boot
# would also try (and fail, crashing the container under `set -eu`) on a
# read-only bind mount.
if [ -z "$(ls -A /var/www/html/data 2>/dev/null)" ]; then
	echo "==> Seeding data/ volume from image"
	cp -a /var/www/seed-data/. /var/www/html/data/
	chown -R www-data:www-data /var/www/html/data
fi
if [ -z "$(ls -A /var/www/html/conf 2>/dev/null)" ]; then
	echo "==> Seeding conf/ volume from image"
	cp -a /var/www/seed-conf/. /var/www/html/conf/
	chown -R www-data:www-data /var/www/html/conf
fi

# conf/local-prod.php holds the authud API key and superuser group — a real
# secret, so it's gitignored in the repo (never baked into the image) and
# instead regenerated from env vars on every boot. This overwrites whatever
# copy is sitting in the conf/ volume, which is fine: it's fully derived,
# never hand-edited like acl.auth.php is via the ACL Manager UI.
cat > /var/www/html/conf/local-prod.php <<PHP
<?php

\$conf['authtype'] = 'authud';
\$conf['plugin']['authud']['endpoint'] = '${AUTHUD_ENDPOINT:?Set AUTHUD_ENDPOINT in .env}';
\$conf['plugin']['authud']['cookiename'] = 'UDSESSIONID';
\$conf['plugin']['authud']['apikey'] = '${AUTHUD_APIKEY:?Set AUTHUD_APIKEY in .env}';
\$conf['plugin']['captcha']['loginprotect'] = 0;
\$conf['superuser'] = '${WIKI_SUPERUSER:-@admin}';

\$conf['cookiename']    = 'UDSESSIONID';
PHP

chown www-data:www-data /var/www/html/conf/local-prod.php

php-fpm -D
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
