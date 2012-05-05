#!/bin/sh

#SLIM_VERSION="master"
SLIM_VERSION="release-1.6.3"
SLIM_EXTRAS_VERSION="master"

INSTALL_DIR=`pwd`
# Create directories

mkdir -p data
mkdir -p data/logs
mkdir -p ext
mkdir -p ext/js

# Default population of DB if not yet initialized
if [ ! -f data/oauth2.sqlite ]
then
    docs/reset_oauth.sh
fi

if [ ! -f data/voot.sqlite ]
then
    docs/reset_voot.sh
fi

# Set permissions
chmod -R o+w data/
chcon -R -t httpd_sys_rw_content_t data/

# Configure

cd config/
for DEFAULTS_FILE in `ls *.defaults`
do
    INI_FILE=`basename ${DEFAULTS_FILE} .defaults`
    if [ ! -f ${INI_FILE} ]
    then
        cp ${DEFAULTS_FILE} ${INI_FILE}
        sed -i "s|/PATH/TO/APP|${INSTALL_DIR}|g" ${INI_FILE}
    fi
done
cd ../

# Installation Of Dependencies

# Slim is a PHP 5 Micro Framework
if [ ! -d ext/Slim ]
then
	cd ext/
	git clone git://github.com/codeguy/Slim.git
    cd Slim/
    git checkout ${SLIM_VERSION}
	cd ../../
else
	cd ext/Slim
    git checkout master
	git pull
    git checkout ${SLIM_VERSION}
	cd ../../
fi

# Slim-Extras
if [ ! -d ext/Slim-Extras ]
then
	cd ext/
	git clone git://github.com/codeguy/Slim-Extras.git
    cd Slim-Extras/
    git checkout ${SLIM_EXTRAS_VERSION}
	cd ../../
else
	cd ext/Slim-Extras
    git checkout master
	git pull
    git checkout ${SLIM_EXTRAS_VERSION}
	cd ../../
fi

# BrowserID
if [ ! -d ext/browserid-session ]
then
	cd ext/
	git clone https://github.com/michielbdejong/browserid-session.git
    cd ..
else
	cd ext/browserid-session
	git pull
	cd ../../
fi

# jQuery
if [ ! -f ext/js/jquery.js ]
then
    wget -O ext/js/jquery.js http://code.jquery.com/jquery.min.js
fi

# JSrender (JavaScript Template Rendering for jQuery)
if [ ! -f ext/js/jsrender.js ]
then
    wget -O ext/js/jsrender.js https://raw.github.com/BorisMoore/jsrender/master/jsrender.js
fi

# JSO (JavaScript OAuth 2 client)
if [ ! -f ext/js/jso.js ]
then
    wget -O ext/js/jso.js https://raw.github.com/andreassolberg/jso/master/jso.js
fi

# Bootstrap
if [ ! -d ext/bootstrap ]
then
	cd ext/
	wget -O bootstrap.zip http://twitter.github.com/bootstrap/assets/bootstrap.zip
	unzip -o -q bootstrap.zip
	rm bootstrap.zip
    cd ../
fi

# Bootstrap Modal
if [ ! -f ext/js/bootstrap-modal.js ]
then
    wget -O ext/js/bootstrap-modal.js http://twitter.github.com/bootstrap/assets/js/bootstrap-modal.js
fi
