FROM dukim/centos-httpd2-php5.4

# Define execution user
USER root

RUN yum -y install curl

# Define working directory
WORKDIR /var/www

# Config php ini in pecl
#RUN pecl config-set php_ini /etc/php5/apache2/php.ini

# Install XDebug
# RUN pecl install xdebug-2.2.7 && docker-php-ext-enable xdebug; exit 0

# Copy app files
COPY ./source/apps /var/www/apps
COPY ./source/sites /var/www/sites

# Copy apache config files
COPY conf/httpd/conf /etc/httpd/conf
COPY conf/httpd/conf.d /etc/httpd/conf.d
COPY conf/httpd/conf.modules.d /etc/httpd/conf.modules.d
COPY conf/httpd-ssl-pass-dialog /usr/libexec/httpd-ssl-pass-dialog

# Copy php.ini file
COPY conf/php.ini /usr/local/lib/php.ini

# Install Composer
RUN cd /var/www/sites/gts-app.pnc.co.il && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    php composer.phar install --ignore-platform-reqs

# Copy init file
COPY conf/init.sh /init.sh
RUN chmod 777 -R /init.sh

# Expose application ports
EXPOSE 80
EXPOSE 443
