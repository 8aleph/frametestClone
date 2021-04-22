FROM php:7.4.1-fpm

COPY *.conf /usr/local/etc/php-fpm.d/

ADD ./config /

# Note: this does not include support for ssl, if you need it, refer
#       to the miniweb docker file for an example of setting it up
RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && \
    echo "UTC" > /etc/timezone && \
    apt-get update && \
    apt-get install -y --no-install-recommends openssl libssl-dev libonig-dev gnupg wget locales apt-transport-https && \
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    locale-gen && \
    # Setup the PHP packages
    docker-php-ext-install mbstring pdo mysqli opcache && \
    pecl install redis-4.1.1 && \
    docker-php-ext-enable redis && \
    apt-get -y clean && \
    apt-get -y autoclean && \
    apt-get -y autoremove && \
    rm -rf /var/lib/apt/lists/* && \
    rm -rf /var/lib/cache/* && \
    rm -rf /var/lib/log/* && \
    rm -rf /tmp/* && \
    # App setup
    mkdir -p /var/lib/php && \
    chown www-data:www-data /var/lib/php && \
    chmod 775 /var/lib/php && \
    mkdir /opt/apptmp && \
    chown www-data:www-data /opt/apptmp && \
    chmod 775 /opt/apptmp && \
    # Setup sessions
    mkdir -p /var/sessions && \
    chown www-data:www-data /var/sessions && \
    # Setup generic cache
    rm -rf /code/cache && \
    mkdir -p /code/cache && \
    # Setup route cache
    mkdir /code/cache/routes && \
    # Setup view cache
    mkdir /code/cache/views && \
    # Setup all cache perms
    chown -R www-data:www-data /code/cache/

# Complains about missing config file from mysqli install, so hack to get it to work.
RUN docker-php-ext-install openssl; exit 0
RUN cp /usr/src/php/ext/openssl/config0.m4 /usr/src/php/ext/openssl/config.m4 && \
    docker-php-ext-install openssl

ADD code /code

ENTRYPOINT [ "bash",  `$(pwd)/scripts/entrypoint.sh` ]

CMD [ "php-fpm" ]
