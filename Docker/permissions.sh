#!/bin/bash
# Make project folders and set perrmisions.
mkdir -p /home/app/current/webroot/ && \
mkdir /home/app/data/ && \
mkdir /home/app/assets/ && \
mkdir /home/project/ && \
mkdir /home/project/assets/ && \
chmod -R 755 /home/app/ && \
chown -R www-data:www-data /home/app/ && \
chmod -R 755 /home/app/current && \
chown -R www-data:www-data /home/app/current && \
chmod -R 755 /home/project/ && \
chown -R www-data:www-data /home/project
