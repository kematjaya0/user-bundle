# Test image for user-bundle, used by docker/test.sh for the PHP x Symfony matrix.
ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-cli

# php:7.4 is based on Debian 11 (bullseye) whose security repository no longer
# serves its packages, so only the main repository is used. Test-only image.
RUN if grep -qs bullseye /etc/apt/sources.list; then sed -i '/bullseye-security/d' /etc/apt/sources.list; fi \
    && apt-get -o Acquire::Retries=5 update \
    && apt-get -o Acquire::Retries=5 install -y --no-install-recommends git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# global symfony/flex so SYMFONY_REQUIRE pins symfony/* versions on composer update
RUN composer global config --no-plugins allow-plugins.symfony/flex true \
    && composer global require --no-interaction --no-progress 'symfony/flex:^1.21|^2'

WORKDIR /app
