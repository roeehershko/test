FROM centos/httpd

USER root

# Define working directory
WORKDIR /var/www

# Install Packages
RUN yum -y install gcc gcc-c++ make automake autoconf bison flex libtool libstdc++-devel wget epel-release
RUN yum -y install libxml2-devel openssl-devel bzip2-devel libcurl-devel libjpeg-devel libpng-devel t1lib-devel gmp-devel libicu-devel libmcrypt-devel readline-devel libtidy-devel libxslt-devel
RUN yum -y install httpd-devel

# Download PHP
COPY ./php-7.2.6.tar.gz /root/php-7.2.6.tar.gz
RUN cd /root && tar -xzvf php-7.2.6.tar.gz

# Compile PHP
RUN cd /root/php-7.2.6 && ./configure \
       --with-libdir=lib64 \
        --prefix=/usr/local \
        --with-layout=PHP \
        --with-pear \
        --with-apxs2 \
		--enable-bcmath \
		--with-bz2 \
		--enable-calendar \
		--with-curl \
		--enable-dom \
		--enable-exif \
		--enable-fileinfo \
		--enable-filter \
		--enable-ftp \
		--with-gd \
		--with-gettext \
		--with-gmp \
		--enable-hash \
		--with-iconv \
		--enable-json \
		--with-libxml \
		--enable-mbstring \
		--with-mcrypt \
		--with-mhash \
		--with-mysql \
		--with-mysqli \
		--with-openssl \
		--enable-pcntl \
		--with-pdo-mysql \
		--with-readline \
		--enable-shmop \
		--enable-soap \
		--enable-sockets \
		--enable-sysvmsg \
		--enable-sysvsem \
		--enable-sysvshm \
		--with-tidy \
		--enable-wddx \
		--with-xsl \
		--enable-zip \
		--with-zlib \
        && make install

# Config php ini in pecl
#RUN pecl config-set php_ini /etc/php5/apache2/php.ini

# Install XDebug
# RUN pecl install xdebug-2.2.7 && docker-php-ext-enable xdebug; exit 0

# Copy app files
#COPY source /var/www

RUN yum -y install mod_ssl
