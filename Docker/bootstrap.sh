#!/bin/bash

echo '***************************** Building Directories ****************************'
mkdir -p /home/app/current/webroot/
mkdir /home/app/data/
chmod -R 755 /home/app/
chown -R www-data:www-data /home/app/


echo '***************************** Configuring Apache *****************************'

sudo service apache2 start

echo '
SSLProtocol All -SSLv2 -SSLv3
SSLHonorCipherOrder On       
SSLCipherSuite  ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:DES-CBC3-SHA:!DSS
ServerName localhost
EnableSendFile off
' >> /etc/apache2/apache2.conf

echo '
<VirtualHost *:80>
    ServerAdmin support@lunarlogic.com
    DocumentRoot /home/app/current/webroot
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
    LogLevel warn

    <Directory /home/app/current/webroot/>
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
    </Directory> 

</VirtualHost>' > /etc/apache2/sites-available/default.conf

# Expect these to be overwritten in production
make-ssl-cert generate-default-snakeoil --force-overwrite
mv /etc/ssl/certs/ssl-cert-snakeoil.pem /etc/ssl/certs/ox.pem
mv /etc/ssl/private/ssl-cert-snakeoil.key /etc/ssl/private/ox.key

echo '
<IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerAdmin support@lunarlogic.com
        DocumentRoot /home/app/current/webroot
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        <Directory /home/app/current/webroot/>
            Options -Indexes +FollowSymLinks -MultiViews
            AllowOverride All
            Require all granted
        </Directory>

        SSLEngine on

        SSLCertificateFile    /etc/ssl/certs/ox.pem
        SSLCertificateKeyFile /etc/ssl/private/ox.key

        <FilesMatch \"\.(cgi|shtml|phtml|php)$\">
                SSLOptions +StdEnvVars
        </FilesMatch>
        BrowserMatch \"MSIE [2-6]\" \
                nokeepalive ssl-unclean-shutdown \
                downgrade-1.0 force-response-1.0
        # MSIE 7 and newer should be able to use keepalive
        BrowserMatch \"MSIE [17-9]\" ssl-unclean-shutdown

    </VirtualHost>
</IfModule>' > /etc/apache2/sites-available/default-ssl.conf

# Clean up Apache2 default site to avoid confusion
a2dissite 000-default.conf
rm /etc/apache2/sites-available/000-default.conf

# Enable our http and https sites.
a2ensite default.conf
a2ensite default-ssl.conf

a2enmod ssl
a2enmod rewrite

apt-get update

echo '***************************** Installing PHP5 *****************************'
apt-get install -y libapache2-mod-php5.6 php5.6-cli php5.6-ldap php5.6-mbstring

echo '***************************** Installing PHP5 tools *****************************'
apt-get install -y php5.6-curl php5.6-mcrypt php5.6-gd php5.6-xdebug php5.6-intl php5.6-dev php5.6-imagick

echo '***************************** Wrapping Up *****************************'
sudo service apache2 restart
