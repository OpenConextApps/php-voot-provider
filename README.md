# PHP VOOT group provider

This project is a tiny stand-alone VOOT group provider. The 
[VOOT specification](http://www.openvoot.org/) is implemented.

# Features

* PDO storage backend for VOOT data and OAuth tokens
* OAuth 2 support
* SAML authentication support ([simpleSAMLphp](http://www.simplesamlphp.org)) 

# Installation

The project includes an install script that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions.

    $ mkdir /var/www/html/voot
    $ git clone git://github.com/fkooman/phpvoot.git /var/www/html/voot
    $ cd /var/www/html/voot
    $ docs/install.sh
    $ cp config/voot.ini.defaults config/voot.ini
  
# Configuration

In the configuration file `config/voot.ini` various aspects can be configured. 
To configure the SAML integration, make sure the following settings are 
correct:

    authenticationMechanism = "SspResourceOwner"

    [oauthSsp]
    sspPath = '/var/simplesamlphp/lib'
    authSource = 'default-sp'
    resourceOwnerIdAttributeName = 'uid'

# Configuring OAuth Consumers

The default OAuth token store contains one OAuth consumer. To add your own you
can use the SQLite command line tool to add some:

    $ echo "INSERT INTO Client VALUES('client_id',NULL,'http://host.tld/redirect_uri','public');" | sqlite3 data/oauth2.sqlite

# Testing

A JavaScript client is included to test the VOOT provider. It uses the 
"implicit grant" OAuth 2 profile to obtain an access token. The client code can 
be found in the `client/vootClient.html`. Modify the client code to point to the
correct OAuth authorization endpoint and API URL.


