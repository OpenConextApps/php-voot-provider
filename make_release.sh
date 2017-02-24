#!/bin/bash

set -e

RELEASE_DIR=${HOME}/Releases
GITHUB_USER=OpenConextApps
PROJECT_NAME=php-voot-provider

if [ -z "$1" ]
then

cat << EOF
Please specify the tag or branch to make a release of.

Examples:
    
    sh make_release.sh 0.1.0
    sh make_release.sh master
    sh make_release.sh develop

If you want to GPG sign the release, you can specify the "sign" parameter, this will
invoke the gpg command line tool to sign it.

   sh make_release 0.1.0 sign

EOF
exit 1
else
    TAG=$1
fi

mkdir -p ${RELEASE_DIR}
rm -rf ${RELEASE_DIR}/${PROJECT_NAME}-${TAG}

# get Composer
(
cd ${RELEASE_DIR}
curl -O https://getcomposer.org/composer.phar
)

# clone the tag
(
cd ${RELEASE_DIR}
git clone -b ${TAG} https://github.com/${GITHUB_USER}/${PROJECT_NAME}.git ${PROJECT_NAME}-${TAG}
)

# run Composer
(
cd ${RELEASE_DIR}/${PROJECT_NAME}-${TAG}
php ${RELEASE_DIR}/composer.phar install
)

# remove Git and Composer files
(
cd ${RELEASE_DIR}/${PROJECT_NAME}-${TAG}
rm -rf .git
rm -f .gitignore
rm -f composer.json
rm -f composer.lock
rm -f make_release.sh
)

# create tarball
(
cd ${RELEASE_DIR}
tar -czf ${PROJECT_NAME}-${TAG}.tar.gz ${PROJECT_NAME}-${TAG}
)

# create checksum file
(
cd ${RELEASE_DIR}
shasum ${PROJECT_NAME}-${TAG}.tar.gz > ${PROJECT_NAME}-${TAG}.sha
)

# sign it if requested
(
if [ -n "$2" ]
then
	if [ "$2" == "sign" ]
	then
		cd ${RELEASE_DIR}
		gpg -o ${PROJECT_NAME}-${TAG}.sha.gpg  --clearsign ${PROJECT_NAME}-${TAG}.sha
	fi
fi
)
