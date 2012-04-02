#!/bin/sh

# create directories and set permissions
mkdir -p data
mkdir -p ext
mkdir -p ext/js
docs/reset_oauth.sh
docs/reset_voot.sh
chmod -R o+w data/
chcon -R -t httpd_sys_rw_content_t data

# slim
if [ ! -d ext/Slim ]
then
	cd ext/
	git clone git://github.com/codeguy/Slim.git
else
	cd ext/Slim
	git pull
fi

####################################
# Dependencies for the demo client #
####################################

# fetch jquery
if [ ! -f ext/js/jquery.js ]
then
    wget -O ext/js/jquery.js -N http://code.jquery.com/jquery.min.js
fi

# fetch jsrender
if [ ! -f ext/js/jsrender.js ]
then
    wget -O ext/js/jsrender.js -N https://raw.github.com/BorisMoore/jsrender/master/jsrender.js
fi
