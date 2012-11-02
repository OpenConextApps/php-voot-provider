#!/bin/sh

rm -rf extlib
mkdir -p extlib

# php-rest-service
(
cd extlib
git clone https://github.com/fkooman/php-rest-service.git
)
