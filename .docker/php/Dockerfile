FROM php:8.5-fpm

#xdebug ARG XDEBUG_VERSION=3.5.1

RUN apt-get update -yqq \
    && apt-get install -yqq --no-install-recommends curl apt-utils debconf apt-transport-https \
    build-essential locales acl wget zip unzip \
;

RUN docker-php-ext-install \
  mysqli \
  pdo_mysql

#xdebug RUN apt-get --no-install-recommends -qy install $PHPIZE_DEPS \
#xdebug     && pecl install xdebug-${XDEBUG_VERSION} \
#xdebug     && docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/php.ini
COPY php-fpm-pool.conf /usr/local/etc/php/pool.d/www.conf
#xdebug COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

SHELL ["/bin/bash", "-o", "pipefail", "-c"]
# RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
#     mv composer.phar /usr/local/bin/composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    # php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"  && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer  && \
    php -r "unlink('composer-setup.php');"

RUN groupadd dev -g 999 && \
    useradd dev -g dev -d /home/dev -m && \
    passwd -d dev

RUN rm -rf /var/lib/apt/lists/* && \
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "es_ES.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen && \
    echo "dev ALL=(ALL) ALL" > /etc/sudoers

COPY init.sh /opt/aos/init.sh

WORKDIR /home/wwwroot/

EXPOSE 9000
# EXPOSE 9003
CMD ["php-fpm"]

ENTRYPOINT ["/opt/aos/init.sh"]