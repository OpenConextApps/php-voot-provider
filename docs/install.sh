#!/bin/sh

# create directories and set permissions
mkdir -p data
mkdir -p ext
mkdir -p ext/js
docs/reset_oauth.sh
docs/reset_voot.sh
chmod -R o+w data/
chcon -R -t httpd_sys_rw_content_t data

# configure
if [ ! -f config/oauth.ini ]
then
        cp config/oauth.ini.defaults config/oauth.ini
        BASE_DIR=`pwd`
        sed -i "s|/var/www/html/voot|${BASE_DIR}|g" config/oauth.ini
fi

if [ ! -f config/voot.ini ]
then
	cp config/voot.ini.defaults config/voot.ini
	BASE_DIR=`pwd`
	sed -i "s|/var/www/html/voot|${BASE_DIR}|g" config/voot.ini
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

####################################
# Dependencies for the demo client #
####################################

# fetch jquery
if [ ! -f ext/js/jquery.js ]
then
    wget -O ext/js/jquery.js http://code.jquery.com/jquery.min.js
fi

# fetch jsrender (JavaScript Template Rendering for jQuery)
if [ ! -f ext/js/jsrender.js ]
then
    wget -O ext/js/jsrender.js https://raw.github.com/BorisMoore/jsrender/master/jsrender.js
fi

# fetch jso (JavaScript OAuth 2 client)
if [ ! -f ext/js/jso.js ]
then
    wget -O ext/js/jso.js https://raw.github.com/andreassolberg/jso/master/jso.js
fi

