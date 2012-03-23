#!/bin/sh
mkdir -p data
docs/reset_oauth.sh
docs/reset_voot.sh
chmod -R o+w data/
chcon -R -t httpd_sys_rw_content_t data
if [ ! -d ext/Slim ]
then
	mkdir -p ext/
	cd ext/
	git clone git://github.com/codeguy/Slim.git
else
	cd ext/Slim
	git pull
fi

