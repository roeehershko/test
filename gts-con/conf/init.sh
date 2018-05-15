#!/bin/bash

rm -f /var/run/apache2/apache2.pid
ln -s "/var/www/sites/gts-con.pnc.co.il" "/var/www/apps/gyro/sites/${CON_HOST}" || true
exec /usr/sbin/apachectl -DFOREGROUND