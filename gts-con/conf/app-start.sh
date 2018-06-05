#!/bin/bash

export $(cat /etc/environment | xargs)
rm -rf /var/www/apps/gyro/sites/* || true
ln -s "/var/www/sites/gts-con.pnc.co.il" "/var/www/apps/gyro/sites/$HOSTNAME"
sudo httpd -k start