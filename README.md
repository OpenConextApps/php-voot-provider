# PHP remoteStorage Provider

# Installation
The project includes an install script that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions.

    $ mkdir /var/www/html/storage
    $ git clone git://github.com/fkooman/phpvoot.git /var/www/html/storage
    $ cd /var/www/html/storage
    $ git checkout remoteStorage
    $ docs/install.sh

# SELinux
The install script already takes care of setting the file permissions of the
`data/` directory to allow Apache to write to the directory. 

# Apache
Also make sure Apache can read and process the `.htaccess` file by giving it
the appropriate permissions on the (in this case) `/var/www/html/storage` 
directory:

    AllowOverride FileInfo

# Configuration
In the configuration file `config/remoteStorage.ini` and `config/oauth.ini` 
various aspects can be configured. To configure the SAML integration (in `oauth.ini`), make sure the following settings are correct:

    authenticationMechanism = "SspResourceOwner"

    ; simpleSAMLphp configuration
    [SspResourceOwner]
    sspPath = "/var/simplesamlphp/lib"
    authSource = "default-sp"
    resourceOwnerIdAttributeName = "uid"
    ;resourceOwnerIdAttributeName = "urn:mace:dir:attribute-def:uid"

# Configuring OAuth Clients

The default OAuth token store contains one OAuth client. To add your own you
can use the SQLite command line tool to add some:

    $ echo "INSERT INTO Client VALUES('client_id',"Client Description",NULL,'http://host.tld/redirect_uri','public');" | sqlite3 data/oauth2.sqlite

In the future a web application will be written for this to allow designated
users to administer client registrations.

# WebFinger
Be sure to place the following file in your web server's root directory at
`.well-known/host-meta`:

    <?xml version="1.0" encoding="UTF-8"?>
    <XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
      <Link rel="lrdd" type="application/xrd+xml" template="http://localhost/storage/lrdd/?uri={uri}"/>
    </XRD>

Accompany this with this `.htaccess` file in the same directory:

    <Files host-meta>
    Header set Access-Control-Allow-Origin "*"
    Header set Content-Type "application/xrd+xml; charset=UTF-8"
    </Files>

Make sure to update the location in the `host-meta` file to point to your 
actual installation. Also use a `https` URI whenever possible!

# Testing
One can test the remoteStorage provider by using 
http://tutorial.unhosted.5apps.com.
