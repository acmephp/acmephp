FROM alpine:edge
ADD acmephp.phar /srv/bin/acme
RUN apk add --no-cache \
    php83 \
    php83-opcache \
    php83-apcu \
    php83-openssl \
    php83-dom \
    php83-mbstring \
    php83-json \
    php83-ctype \
    php83-posix \
    php83-simplexml \
    php83-xmlwriter \
    php83-xml \
    php83-phar \
    php83-curl \
    php83-fileinfo \
    php83-sodium \
    ca-certificates

WORKDIR /srv
RUN chmod +x /srv/bin/acme
RUN echo "date.timezone = UTC" > /etc/php83/conf.d/symfony.ini \
 && echo "opcache.enable_cli=1" > /etc/php83/conf.d/opcache.ini \
 && echo "opcache.file_cache='/tmp/opcache'" >> /etc/php83/conf.d/opcache.ini \
 && echo "opcache.file_update_protection=0" >> /etc/php83/conf.d/opcache.ini \
 && mkdir /tmp/opcache

ENTRYPOINT ["/srv/bin/acme"]
CMD ["list"]
