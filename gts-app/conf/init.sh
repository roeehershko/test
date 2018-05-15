#!/bin/bash

ln -s "/var/www/sites/gts-app.pnc.co.il" "/var/www/apps/gyro/sites/${APP_HOST}" || true
exec /usr/sbin/apachectl -DFOREGROUND