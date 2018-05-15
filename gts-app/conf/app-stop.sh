#!/bin/bash

export $(cat /etc/environment | xargs)
httpd -k stop