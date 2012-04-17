#!/bin/sh

# create directories and set permissions
mkdir -p data
mkdir -p data/files
mkdir -p ext
mkdir -p ext/js
docs/reset_oauth.sh
chmod -R o+w data/
chcon -R -t httpd_sys_rw_content_t data

# configure
if [ ! -f config/oauth.ini ]
then
        cp config/oauth.ini.defaults config/oauth.ini
        BASE_DIR=`pwd`
        sed -i "s|/var/www/html/voot|${BASE_DIR}|g" config/oauth.ini
fi

if [ ! -f config/remoteStorage.ini ]
then
	cp config/remoteStorage.ini.defaults config/remoteStorage.ini
	BASE_DIR=`pwd`
	sed -i "s|/var/www/html/voot|${BASE_DIR}|g" config/remoteStorage.ini
fi

# slim
if [ ! -d ext/Slim ]
then
	cd ext/
	git clone git://github.com/codeguy/Slim.git
	cd ..
else
	cd ext/Slim
	git pull
	cd ../../
fi

