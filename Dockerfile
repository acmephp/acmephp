FROM ubuntu:20.04

# Set timezone
ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Update
RUN apt-get update && apt-get dist-upgrade -y

# Install PHP 8
RUN apt-get install -y software-properties-common apt-transport-https \
  && add-apt-repository ppa:ondrej/php -y \
  && apt-get update \
  && apt-get install php8.0-cli -y

# Install composer 2
RUN apt-get update \
  && apt-get install curl -y \
  && curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/bin/composer \
  && chmod +x /usr/bin/composer

WORKDIR /srv

# Composer dependencies
RUN apt-get install -y \
  php8.0-phar \
  php8.0-iconv \
  php8.0-mbstring \
  php8.0-curl \
  php8.0-ctype \
  php8.0-opcache \
  php8.0-sockets \
  php8.0-simplexml \
  php8.0-dom \
  php8.0-tokenizer \
  php8.0-apcu \
  php8.0-posix \
  php8.0-xmlwriter \
  php8.0-xml \
  php8.0-zip \
  ca-certificates

RUN echo "opcache.enable_cli=1" > /etc/php/8.0/cli/conf.d/opcache.ini \
  && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php/8.0/cli/conf.d/opcache.ini \
  && echo "opcache.file_update_protection=0" >> /etc/php/8.0/cli/conf.d/opcache.ini \
  && mkdir /tmp/opcache

COPY composer.json /srv/

RUN composer install --no-dev --no-scripts --optimize-autoloader \
   && composer require "daverandom/libdns:^2.0.1" --no-scripts --no-suggest --optimize-autoloader

COPY ./src /srv/src
#COPY ./res /srv/res
COPY ./bin /srv/bin

#RUN composer warmup-opcode -- /srv

RUN echo "date.timezone = UTC" > /etc/php/8.0/cli/conf.d//symfony.ini \
 && echo "opcache.enable_cli=1" > /etc/php/8.0/cli/conf.d//opcache.ini \
 && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php/8.0/cli/conf.d/opcache.ini \
 && echo "opcache.file_update_protection=0" >> /etc/php/8.0/cli/conf.d/opcache.ini

ENTRYPOINT ["/srv/bin/acme"]
CMD ["list"]
