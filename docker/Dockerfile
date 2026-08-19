# syntax=docker/dockerfile:1

# Caddy binary only — same web server/config language as the host-Caddy this
# is replacing (see docker/Caddyfile), so the rewrite rules could be ported
# verbatim instead of translated to nginx location blocks.
FROM caddy:2.8-alpine AS caddy

FROM php:8.3-fpm-alpine

# Runtime shared libs stay installed permanently; -dev/headers packages are
# grouped under the `.build-deps` virtual so `apk del .build-deps` removes
# exactly those and nothing else - recent Alpine apk otherwise treats a
# plain (non-virtual) `apk del somepkg-dev` as license to also prune runtime
# libs it thinks are now orphaned (bit us here: gd/intl/zip failed to load
# at runtime with "No such file or directory" on libpng16/libicuio/libzip
# after a first pass without --virtual).
RUN apk add --no-cache freetype libjpeg-turbo libpng icu-libs libzip oniguruma libcurl \
	&& apk add --no-cache --virtual .build-deps \
		freetype-dev libjpeg-turbo-dev libpng-dev \
		icu-dev libzip-dev curl-dev oniguruma-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" gd intl mbstring zip curl opcache \
	&& apk del .build-deps

COPY --from=caddy /usr/bin/caddy /usr/bin/caddy

COPY docker/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile

WORKDIR /var/www/html

# .dockerignore drops _test/, .git/, .github/, docker/ itself, and anything
# gitignored (data/*, conf/local-prod.php, vendor caches) — the tracked
# seed content only. data/ and conf/ get copied a second time below into
# untouched seed-* paths, since the named volumes in compose.yml will shadow
# whatever lands directly under data/ and conf/ at container start.
COPY . .
RUN cp -a data /var/www/seed-data \
	&& cp -a conf /var/www/seed-conf \
	&& chown -R www-data:www-data /var/www/html /var/www/seed-data /var/www/seed-conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
