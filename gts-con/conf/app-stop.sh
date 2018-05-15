#!/bin/bash

export $(cat /etc/environment | xargs)
sudo httpd -k stop