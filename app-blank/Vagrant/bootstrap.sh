#!/usr/bin/env bash

# https://github.com/Divi/VagrantBootstrap


# ------------------------------------------------
# Project Name, set in Vagrantfile
# ------------------------------------------------
projectName=$1


# ------------------------------------------------
# Update the box release repositories
# ------------------------------------------------
echo '***************************** Setting up needed Repos *****************************'
apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10
echo 'deb http://downloads-distro.mongodb.org/repo/ubuntu-upstart dist 10gen' | tee /etc/apt/sources.list.d/mongodb.list


echo '***************************** Apt Update *****************************'
export DEBIAN_FRONTEND=noninteractive
apt-get update


echo '***************************** System settings *****************************'

# Terminal
echo "PS1='$projectName(\u):\w# ' " >> /root/.bashrc
echo "PS1='$projectName(\u):\w\$ ' " >> /home/vagrant/.bashrc


# ------------------------------------------------
# Apache
# ------------------------------------------------
echo '***************************** Installing and configuring Apache *****************************'
apt-get install -y apache2

# Add ServerName to httpd.conf for localhost
echo "ServerName localhost
User vagrant
Group vagrant
EnableSendFile off" > /etc/apache2/httpd.conf

echo "<VirtualHost *:80>
ServerAdmin webmaster@localhost
DocumentRoot /home/project/$projectName/webroot/

CustomLog ${APACHE_LOG_DIR}/access.log combined
ErrorLog ${APACHE_LOG_DIR}/error.log
LogLevel warn

<Directory /home/project/$projectName/webroot/>
    Options -Indexes +FollowSymLinks -MultiViews
    AllowOverride All
    Order allow,deny
    allow from all
</Directory>

</VirtualHost>" > /etc/apache2/sites-available/default

echo "<IfModule mod_ssl.c>
<VirtualHost *:443>
ServerAdmin webmaster@localhost
DocumentRoot /home/project/$projectName/webroot/

CustomLog ${APACHE_LOG_DIR}/access.log combined
ErrorLog ${APACHE_LOG_DIR}/error.log
LogLevel warn

<Directory /home/project/$projectName/webroot/>
    Options -Indexes +FollowSymLinks -MultiViews
    AllowOverride All
    Order allow,deny
    allow from all
</Directory>

SSLEngine on

SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
<FilesMatch \"\.(cgi|shtml|phtml|php)$\">
        SSLOptions +StdEnvVars
</FilesMatch>
BrowserMatch \"MSIE [2-6]\" \
        nokeepalive ssl-unclean-shutdown \
        downgrade-1.0 force-response-1.0
# MSIE 7 and newer should be able to use keepalive
BrowserMatch \"MSIE [17-9]\" ssl-unclean-shutdown

</VirtualHost>
</IfModule>" > /etc/apache2/sites-available/default-ssl


echo '***************************** Add extra mod and generate testing certificate *****************************'
make-ssl-cert generate-default-snakeoil --force-overwrite
a2ensite default-ssl
a2enmod ssl
a2enmod rewrite
# vagrant (apache user) needs to read this
chgrp vagrant -R /etc/ssl/private/

# ------------------------------------------------
# PHP 5.x
# ------------------------------------------------
echo '***************************** Installing PHP5 *****************************'
apt-get install -y php5 libapache2-mod-php5 php5-cli php5-ldap


echo '***************************** Installing PHP5 tools *****************************'
apt-get install -y php5-curl php5-mcrypt php5-gd php-pear php5-xdebug php5-intl php5-dev


echo '***************************** Writing php.ini *****************************'
echo '
engine = On
output_buffering = 4096
implicit_flush = Off
allow_call_time_pass_reference = Off
safe_mode = Off
disable_functions = pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,
zend.enable_gc = On

max_execution_time = 30
max_input_time = 60
;max_input_nesting_level = 64
; max_input_vars = 1000
memory_limit = 128M
post_max_size = 8M

file_uploads = On
upload_max_filesize = 2M
max_file_uploads = 20

allow_url_fopen = On
allow_url_include = Off

error_reporting = E_ALL & ~E_DEPRECATED
display_errors = Off
display_startup_errors = On
log_errors = On
log_errors_max_len = 1024
ignore_repeated_errors = Off
ignore_repeated_source = Off
report_memleaks = On

html_errors = On
variables_order = "GPCS"
request_order = "GP"

register_globals = Off
register_long_arrays = Off
register_argc_argv = Off

auto_globals_jit = On
magic_quotes_gpc = Off
magic_quotes_runtime = Off
magic_quotes_sybase = Off
default_mimetype = "text/html"
enable_dl = Off

[Date]
date.timezone ="America/Los_Angeles"

[mail function]
; For Win32 only.
SMTP = localhost
smtp_port = 25

; For Win32 only.
;sendmail_from = me@example.com

; For Unix only.  You may supply arguments as well (default: "sendmail -t -i").
;sendmail_path =

; Add X-PHP-Originating-Script: that will include uid of the script followed by the filename
mail.add_x_header = On


[SQL]
sql.safe_mode = Off

[MySQL]
mysql.allow_local_infile = On
mysql.allow_persistent = On
mysql.cache_size = 2000
mysql.max_persistent = -1
mysql.max_links = -1
mysql.connect_timeout = 60
mysql.trace_mode = Off

[MySQLi]
mysqli.max_persistent = -1
mysqli.allow_persistent = On
mysqli.max_links = -1
mysqli.cache_size = 2000
mysqli.default_port = 3306
mysqli.reconnect = Off

[XDebug]
xdebug.remote_enable=true
xdebug.remote_port="9000"
xdebug.profiler_enable=1
xdebug.profiler_output_dir="\tmp"
xdebug.var_display_max_children=-1
xdebug.var_display_max_data=-1
xdebug.var_display_max_depth=-1
xdebug.scream=1
xdebug.cli_color=1
xdebug.show_local_vars=1
xdebug.idekey = "vagrant"
xdebug.remote_enable = 1
xdebug.remote_autostart = 0
xdebug.remote_handler=dbgp
xdebug.remote_log="/var/log/xdebug/xdebug.log"
xdebug.remote_host=10.0.2.2

extension=mongo.so

' > /etc/php5/apache2/php.ini


echo "***************************** Installing Packages needed to build under PECL *****************************"
apt-get install -y build-essential git curl g++ libssl-dev apache2-utils


echo "***************************** Installing xdebug *****************************"
pecl install xdebug


echo "***************************** Installing Mongo *****************************"
apt-get install mongodb-10gen
# MongoDB driver, has to be after PHP Pear install
pecl install mongo-1.3.7


# Rock Mongo http://localhost:8080/rock
# ------------------------------------------------
echo '***************************** Installing Rock Mongo - http://localhost:8080/rock *****************************'
git clone https://github.com/iwind/rockmongo.git /home/project/rockmongo
echo "# rockmongo default Apache configuration
Alias /rock /home/project/rockmongo
<Directory /home/project/rockmongo>
        Options FollowSymLinks
        DirectoryIndex index.php
</Directory>
" > /etc/apache2/conf.d/rockmongo.conf


echo '***************************** Installing Pimp My Log - http://localhost:8080/pml *****************************'
git clone https://github.com/potsky/PimpMyLog.git /home/project/pimpmylog

echo "# pimpmylog default Apache configuration
Alias /pml /home/project/pimpmylog
<Directory /home/project/pimpmylog>
        Options FollowSymLinks
        DirectoryIndex index.php
</Directory>
" > /etc/apache2/conf.d/pimpmylog.conf

mkdir /home/project/upload #default upload location for OxCMS

# ------------------------------------------------
# Finish up
# ------------------------------------------------
# restart apache
service apache2 restart
# set permission for vagrant user
chown -R vagrant /home/project

echo '***************************** Completed bootstrap.sh for $projectName *****************************'
echo '
** Helpful Vagrant commands **
vagrant up
vagrant suspend
vagrant halt
vagrant ssh
vagrant global-status --prune && vagrant global-status
'
