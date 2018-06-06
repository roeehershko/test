FROM php:apache-stretch

# Define execution user
USER root

RUN apt-get update && apt-get install -y \
        curl git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli

# Define working directory
WORKDIR /var/www/app

# Config php ini in pecl
#RUN pecl config-set php_ini /etc/php5/apache2/php.ini

# Install XDebug
# RUN pecl install xdebug-2.2.7 && docker-php-ext-enable xdebug; exit 0

# Copy app files
COPY ./ /var/www/app

# Install Composer
RUN cd /var/www/app && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    php composer.phar install --ignore-platform-reqs --prefer-dist

# Copy apache config files
COPY ./conf/default.conf /etc/apache2/sites-enabled/default.conf
RUN mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load
#COPY conf/httpd/conf.d /etc/httpd/conf.d
#COPY conf/httpd/conf.modules.d /etc/httpd/conf.modules.d

# Copy php.ini file
# COPY conf/php.ini /usr/local/lib/php.ini

# Expose application ports
EXPOSE 80
EXPOSE 443
