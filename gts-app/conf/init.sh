#!/bin/bash

ln -s "/var/www/sites/gts-app.pnc.co.il" "/var/www/sites/${HOSTNAME}" || true
exec /usr/sbin/apachectl -DFOREGROUND
