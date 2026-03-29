FROM composer:2.8 AS composer

FROM php:8.3-cli-bookworm

ARG APP_DIR=/opt/apps/pix-withdrawal
ARG REDIS_EXTENSION_VERSION=6.1.0
ARG RDKAFKA_EXTENSION_VERSION=6.0.5
ARG SWOOLE_EXTENSION_VERSION=5.1.5

ENV APP_DIR=${APP_DIR} \
    APP_TIMEZONE=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_MAX_PARALLEL_HTTP=16 \
    COMPOSER_PROCESS_TIMEOUT=600 \
    PATH="${APP_DIR}/vendor/bin:${PATH}" \
    TZ=UTC

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        default-mysql-client \
        git \
        gosu \
        libbrotli-dev \
        libcurl4-openssl-dev \
        libsasl2-dev \
        librdkafka-dev \
        libicu-dev \
        libonig-dev \
        libssl-dev \
        libzip-dev \
        libzstd-dev \
        pkg-config \
        unzip \
        zip \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && pecl install \
        redis-${REDIS_EXTENSION_VERSION} \
        rdkafka-${RDKAFKA_EXTENSION_VERSION} \
        swoole-${SWOOLE_EXTENSION_VERSION} \
    && docker-php-ext-enable redis rdkafka swoole \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR ${APP_DIR}

RUN mkdir -p \
    ${APP_DIR}/runtime/cache \
    ${APP_DIR}/runtime/container \
    ${APP_DIR}/runtime/container/proxy \
    ${APP_DIR}/runtime/logs \
    ${APP_DIR}/runtime/logs/application \
    ${APP_DIR}/runtime/logs/php \
    ${APP_DIR}/storage/tmp

COPY docker/entrypoint/app.sh /usr/local/bin/app-entrypoint
COPY docker/entrypoint/http.sh /usr/local/bin/http-entrypoint
COPY docker/entrypoint/cli.sh /usr/local/bin/cli-entrypoint
COPY docker/php/conf.d/application.ini /usr/local/etc/php/conf.d/zz-application.ini
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

RUN chmod +x /usr/local/bin/app-entrypoint /usr/local/bin/http-entrypoint /usr/local/bin/cli-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["php", "-v"]
