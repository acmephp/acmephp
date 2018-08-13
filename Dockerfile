FROM alpine:3.8 AS builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /srv

# Composer dependencies
RUN apk add --no-cache \
        php7 \
        php7-phar \
        php7-json \
        php7-iconv \
        php7-mbstring \
        php7-curl \
        php7-ctype \
        php7-opcache \
        php7-sockets \
        php7-openssl

RUN composer global require "hirak/prestissimo" "jderusse/composer-warmup"

RUN echo "opcache.enable_cli=1" > /etc/php7/conf.d/opcache.ini \
 && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php7/conf.d/opcache.ini \
 && echo "opcache.file_update_protection=0" >> /etc/php7/conf.d/opcache.ini \
 && mkdir /tmp/opcache

COPY composer.json /srv/

# App dependencies
RUN apk add --no-cache \
        php7-simplexml \
        php7-dom \
        php7-tokenizer

RUN composer install --no-dev --no-scripts --no-suggest --optimize-autoloader \
 && composer require "daverandom/libdns:^2.0.1" --no-scripts --no-suggest --optimize-autoloader

COPY ./src /srv/src
COPY ./res /srv/res
COPY ./bin /srv/bin

RUN composer warmup-opcode -- /srv

# =============================

FROM alpine:3.8

WORKDIR /srv

# PHP
RUN apk add --no-cache \
        php7 \
        php7-opcache \
        php7-apcu \
        php7-openssl \
        php7-dom \
        php7-mbstring \
        php7-json \
        php7-ctype \
        php7-posix \
        php7-simplexml \
        php7-xmlwriter \
        php7-xml \
        ca-certificates

RUN echo "date.timezone = UTC" > /etc/php7/conf.d/symfony.ini \
 && echo "opcache.enable_cli=1" > /etc/php7/conf.d/opcache.ini \
 && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php7/conf.d/opcache.ini \
 && echo "opcache.file_update_protection=0" >> /etc/php7/conf.d/opcache.ini \
 && mkdir /tmp/opcache

ENTRYPOINT ["/srv/bin/acme"]
CMD ["list"]

COPY --from=builder /tmp/opcache /tmp/opcache
COPY --from=builder /srv /srv
