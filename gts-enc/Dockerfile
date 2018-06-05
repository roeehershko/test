FROM dukim/centos-httpd2-php5.4

# Define execution user
USER root

# Install CURL (Healthcheck tool)
RUN yum -y install curl

# Define working directory
WORKDIR /var/www

# Config php ini in pecl
#RUN pecl config-set php_ini /etc/php5/apache2/php.ini

# Install XDebug
# RUN pecl install xdebug-2.2.7 && docker-php-ext-enable xdebug; exit 0

# Copy app files
COPY ./source/sites /var/www/sites

# Copy apache config files
COPY conf/httpd/conf /etc/httpd/conf
COPY conf/httpd/conf.d /etc/httpd/conf.d
COPY conf/httpd/conf.modules.d /etc/httpd/conf.modules.d

# Copy php.ini file
COPY conf/php.ini /usr/local/lib/php.ini

# Expose application ports
EXPOSE 80
EXPOSE 443

# Copy init file
COPY conf/init.sh /init.sh

ENTRYPOINT ["/init.sh"]
