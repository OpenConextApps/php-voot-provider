#!/bin/sh

#SLIM_VERSION="master"
SLIM_VERSION="release-1.6.3"
SLIM_EXTRAS_VERSION="master"

# Create directories
rm -rf ext/
mkdir -p ext
mkdir -p ext/js

# Slim is a PHP 5 Micro Framework
cd ext/
git clone git://github.com/codeguy/Slim.git
cd Slim/
git checkout ${SLIM_VERSION}
cd ../../

# Slim-Extras
cd ext/
git clone git://github.com/codeguy/Slim-Extras.git
cd Slim-Extras/
git checkout ${SLIM_EXTRAS_VERSION}
cd ../../

# jQuery
wget -O ext/js/jquery.js http://code.jquery.com/jquery.min.js

# JSrender (JavaScript Template Rendering for jQuery)
wget -O ext/js/jsrender.js https://raw.github.com/BorisMoore/jsrender/master/jsrender.js

# JSO (JavaScript OAuth 2 client)
wget -O ext/js/jso.js https://raw.github.com/andreassolberg/jso/master/jso.js

# Bootstrap
cd ext/
wget -O bootstrap.zip http://twitter.github.com/bootstrap/assets/bootstrap.zip
unzip -o -q bootstrap.zip
rm bootstrap.zip
cd ../

# Bootstrap Modal
wget -O ext/js/bootstrap-modal.js http://twitter.github.com/bootstrap/assets/js/bootstrap-modal.js

# Bootstrap Tooltip
wget -O ext/js/bootstrap-tooltip.js http://twitter.github.com/bootstrap/assets/js/bootstrap-tooltip.js

# Bootstrap Popover
wget -O ext/js/bootstrap-popover.js http://twitter.github.com/bootstrap/assets/js/bootstrap-popover.js

