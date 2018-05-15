#!/bin/bash

export $(cat /etc/environment | xargs)
rm -rf /var/www/apps/gyro/sites/* || true
ln -s "/var/www/sites/gts-app.pnc.co.il" "/var/www/apps/gyro/sites/$APP_HOST"
httpd -k start