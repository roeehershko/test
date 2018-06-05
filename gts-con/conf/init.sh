#!/bin/bash

ln -s "/var/www/sites/gts-con.pnc.co.il" "/var/www/apps/gyro/sites/${HOSTNAME}" || true
exec /usr/sbin/apachectl -DFOREGROUND