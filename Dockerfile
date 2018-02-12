FROM php:5.6-apache

MAINTAINER Lunar Logic <support@lunarlogic.com>

#needed for postfix installation 
ENV DEBIAN_FRONTEND noninteractive

COPY Docker/php.ini /usr/local/etc/php/php.ini
COPY Docker/xdebug.ini /uslocal/etc/php/conf.d/xdebug.ini

# Set up Apache configs.
COPY apache_configs/ /etc/apache2
RUN echo '\n\ 
        SSLProtocol All -SSLv2 -SSLv3 \n\
        SSLHonorCipherOrder On \n\
        SSLCipherSuite  ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:DES-CBC3-SHA:!DSS \n\
        ServerName localhost \n\
        EnableSendFile off \n\
' >> /etc/apache2/apache2.conf

# Make project folders and set perrmisions.
RUN mkdir -p /home/app/current/webroot/ && \
    mkdir /home/app/data/ && \
    mkdir /home/app/assets/ && \
    mkdir /home/project/ && \
    mkdir /home/project/assets/ && \
    chmod -R 755 /home/app/ && \
    chown -R www-data:www-data /home/app/ && \
    chmod -R 755 /home/project/ && \
    chown -R www-data:www-data /home/project

#postfix configs
RUN echo "postfix postfix/mailname string localhost" | debconf-set-selections  && \
    echo "postfix postfix/main_mailer_type string 'Internet Site'" | debconf-set-selections


# Install packages. 
RUN apt-get update && apt-get install -y ssl-cert libssl-dev wget postfix dialog libmagickwand-dev imagemagick
# install mail logging disabled by default
#RUN apt-get install -y syslog-ng syslog-ng-core
RUN pecl channel-update pecl.php.net
RUN pecl install mongo
RUN pecl install xdebug-2.2.7
RUN pecl install imagick

# Set up temp certs, this will be replaced in production. 
RUN make-ssl-cert generate-default-snakeoil --force-overwrite && \
    mv /etc/ssl/certs/ssl-cert-snakeoil.pem /etc/ssl/certs/ox.pem && \ 
    mv /etc/ssl/private/ssl-cert-snakeoil.key /etc/ssl/private/ox.key

# copy config file for post fix after installing and creating keys.
COPY Docker/main.cf /etc/postfix/main.cf

# Enable our http and https sites.
RUN a2dissite 000-default.conf && \
    rm /etc/apache2/sites-available/000-default.conf && \
    a2ensite default.conf && \
    a2ensite default-ssl.conf && \
    a2enmod ssl && \
    a2enmod rewrite

# install phpunit for unit tests
RUN wget https://phar.phpunit.de/phpunit-4.8.0.phar && \ 
    chmod +x phpunit-4.8.0.phar && \ 
    mv phpunit-4.8.0.phar /usr/bin/phpunit

#install composer 
RUN EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig) && \ 
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');") && \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then  >&2 echo 'ERROR: Invalid installer signature' && rm composer-setup.php \
    else; php composer-setup.php --quiet && \
    rm composer-setup.php && \ 
    mv composer.phar /usr/local/bin/composer; \
    fi

WORKDIR /home/app/current

# copy ox things
COPY ./app-blank .
COPY ./ox ./ox


RUN service apache2 restart
RUN service postfix restart

CMD ["/bin/bash", "-c","service postfix start && /usr/local/bin/apache2-foreground"]
