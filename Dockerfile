FROM php:5.6-apache

MAINTAINER Lunar Logic <support@lunarlogic.com>


COPY Docker/php.ini /usr/local/etc/php/php.ini
COPY Docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

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
    chmod -R 755 /home/app/ && \
    chown -R www-data:www-data /home/app/

# Install packages. 
RUN apt-get update && apt-get install -y ssl-cert libssl-dev  

# Set up temp certs, this will be replaced in production. 
Run make-ssl-cert generate-default-snakeoil --force-overwrite && \
    mv /etc/ssl/certs/ssl-cert-snakeoil.pem /etc/ssl/certs/ox.pem && \ 
    mv /etc/ssl/private/ssl-cert-snakeoil.key /etc/ssl/private/ox.key

# Enable our http and https sites.
RUN a2dissite 000-default.conf && \
    rm /etc/apache2/sites-available/000-default.conf && \
    a2ensite default.conf && \
    a2ensite default-ssl.conf && \
    a2enmod ssl && \
    a2enmod rewrite


WORKDIR /home/app/current

# copy ox things
COPY ./app-blank .
COPY ./ox ./ox

# Mongo php drive installation.
RUN pecl install mongo

RUN service apache2 restart

