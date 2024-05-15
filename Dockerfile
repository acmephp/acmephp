FROM ubuntu:22.04

# Set timezone
ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Update
RUN apt-get update && apt-get dist-upgrade -y

# Install composer
RUN apt-get update && apt-get install composer -y

WORKDIR /srv

# Composer dependencies
RUN apt-get install -y \
  php8.1-phar \
  php8.1-iconv \
  php8.1-mbstring \
  php8.1-curl \
  php8.1-ctype \
  php8.1-opcache \
  php8.1-sockets \
  php8.1-simplexml \
  php8.1-dom \
  php8.1-tokenizer \
  php8.1-apcu \
  php8.1-posix \
  php8.1-xmlwriter \
  php8.1-xml \
  php8.1-zip \
  php8.1-ftp \
  ca-certificates

RUN echo "opcache.enable_cli=1" > /etc/php/8.1/cli/conf.d/opcache.ini \
  && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php/8.1/cli/conf.d/opcache.ini \
  && echo "opcache.file_update_protection=0" >> /etc/php/8.1/cli/conf.d/opcache.ini \
  && mkdir /tmp/opcache

COPY composer.json /srv/

RUN composer install --no-dev --no-scripts --optimize-autoloader \
   && composer require "daverandom/libdns:^2.0.1" --no-scripts --no-suggest --optimize-autoloader

COPY ./src /srv/src
#COPY ./res /srv/res
COPY ./bin /srv/bin

#RUN composer warmup-opcode -- /srv

RUN echo "date.timezone = UTC" > /etc/php/8.1/cli/conf.d//symfony.ini \
 && echo "opcache.enable_cli=1" > /etc/php/8.1/cli/conf.d//opcache.ini \
 && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php/8.1/cli/conf.d/opcache.ini \
 && echo "opcache.file_update_protection=0" >> /etc/php/8.1/cli/conf.d/opcache.ini

ENTRYPOINT ["/srv/bin/acme"]
CMD ["list"]
