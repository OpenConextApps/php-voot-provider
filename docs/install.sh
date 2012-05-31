#!/bin/sh
INSTALL_DIR=`pwd`

if [ $# -eq 0 ]
then
    HOST="http://localhost/phpvoot"
else
    HOST=$1
fi

# Create directories
mkdir -p data
mkdir -p data/logs

# Default population of DB if not yet initialized
rm data/oauth2.sqlite
cat docs/oauth.sql | sed "s|http://localhost/phpvoot|$HOST|" | sqlite3 data/oauth2.sqlite
chmod o+w data/oauth2.sqlite

rm data/voot.sqlite
sqlite3 data/voot.sqlite < docs/voot.sql
sqlite3 data/voot.sqlite < docs/voot_demo_data.sql
chmod o+w data/voot.sqlite

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

# Apache Configuration File
echo "************************"
echo "* Apache Configuration *"
echo "************************"
echo "---- cut ----"
cat docs/apache.conf | sed "s|/PATH/TO/APP|${INSTALL_DIR}|g"
echo "---- cut ----"

