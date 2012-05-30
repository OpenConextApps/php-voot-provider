# PHP VOOT group provider

This project is a stand-alone VOOT group provider. The 
[VOOT specification](http://www.openvoot.org/) is implemented.

# Features
* PDO storage backend for VOOT data and OAuth tokens
* LDAP backend for VOOT
* Only OAuth 2 support
* SAML authentication support ([simpleSAMLphp](http://www.simplesamlphp.org)) 
* BrowserID support (almost)

# Requirements
The installation requirements on Fedora/CentOS can be installed like this:

    $ su -c 'yum install git php-pdo php php-ldap httpd mod_xsendfile wget'

On Debian/Ubuntu:

    $ sudo apt-get install git sqlite3 php5 php5-sqlite wget unzip libapache2-mod-xsendfile php5-ldap

# Installation
The project includes install scripts that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions.

    $ cd /var/www/html
    $ git clone git://github.com/fkooman/phpvoot.git
    $ cd phpvoot
    $ docs/install_dependencies.sh

Now you can configure the installation and set some default OAuth client
registrations. Make sure to replace the URI with the full URI to your \
installation.

    $ docs/install.sh https://localhost/phpvoot

On Ubuntu (Debian) you would typically install in `/var/www/phpvoot` and not in
`/var/www/html/phpvoot`.

# SELinux
The install script already takes care of setting the file permissions of the
`data/` directory to allow Apache to write to the directory. However, if you
want to use the LDAP backend to retrieve group information Apache also needs
the permission to access LDAP servers. If you want to use the BrowserID 
authentication plugin you also need to give Apache permission to access the 
network. These permissions can be given by using `setsebool` as root:

    $ sudo setsebool -P httpd_can_connect_ldap=on
    $ sudo setsebool -P httpd_can_network_connect=on

This is only for Red Hat based Linux distributions like RHEL, CentOS and 
Fedora.

# Apache
There is an example configuration file in `docs/apache.conf`. 

On Red Hat based distributions the file can be placed in 
`/etc/httpd/conf.d/phpvoot.conf`. On Debian based distributions the file can
be placed in `/etc/apache2/conf.d/phpvoot`. Be sure to modify it to suit your 
environment and do not forget to restart Apache. 

The install script from the previous section outputs a config for your system
which replaces the `/PATH/TO/APP` with the actual directory.

# Configuration
In the configuration file `config/voot.ini` and `config/oauth.ini` various 
aspects can be configured. To configure the SAML integration (in `oauth.ini`), 
make sure the following settings are correct:

    authenticationMechanism = "SspResourceOwner"

    ; simpleSAMLphp configuration
    [SspResourceOwner]
    sspPath = "/var/simplesamlphp/lib"
    authSource = "default-sp"

    resourceOwnerIdAttributeName = "uid"
    resourceOwnerDisplayNameAttributeName = "cn"
    ;resourceOwnerIdAttributeName = "urn:mace:dir:attribute-def:uid"
    ;resourceOwnerDisplayNameAttributeName = "urn:mace:dir:attribute-def:displayName"

## LDAP 
It is possible to use an LDAP server as backend to retrieve group membership.
It depends on your LDAP server configuration how to configure this. It is 
always helpful to start out with some `ldapsearch` commands to see what will 
work for your setup. Below is an example based on searching for `uid`:

    $ ldapsearch -x -H ldap://localhost -b "ou=People,dc=example,dc=org" "(uid=fkooman)" dn

This will retrieve the `distinguishedName` (DN) of that entry which can then in
turn be used to query for the users's groups:

    $ ldapsearch -x -H ldap://localhost -b "ou=Groups,dc=example,dc=org" "(uniqueMember=uid=fkooman,ou=People,dc=example,dc=org)" cn description

This works as well on Microsoft Active Directory servers (it does need a "bind" 
though in the default configuration):

    $ ldapsearch -H ldap://ad.example.org -b "cn=Users,dc=example,dc=org" -D "cn=Administrator,cn=Users,dc=example,dc=org" -w s3cr3t "(samAccountName=fkooman)" dn

Now to fetch the groups for the user:

    $ ldapsearch -H ldap://ad.example.org -b "cn=Users,dc=example,dc=org" -D "cn=Administrator,cn=Users,dc=example,dc=org" -w s3cr3t "(member=CN=François Kooman,CN=Users,DC=example,DC=org)" cn description

This can all be configured in `config/voot.ini`.

To test the configuration of your LDAP settings it is possible to use the 
`LdapTest.php` script in the `docs/` directory. First configure LDAP in 
`config/voot.ini` and then run the script like this:

    $ php docs/LdapTest.php fkooman

This should return an `array` with the group information. If it does not work,
make sure you match the configuration values with the `ldapsearch` commands 
that do work.

# Configuring OAuth Clients

If you want to use the JS management interface you need to modify the API 
endpoint in `manage/manage.js`:

    var apiRoot = 'http://localhost/phpvoot';

To (in this example):

    var apiRoot = 'https://www.example.org/phpvoot';

Once this is done you can manage the OAuth client registrations by going to the
URL configured above at `https://www.example.org/phpvoot/manage/index.html`. 

Make sure the user identifiers you want to allow `admin` permissions are listed 
in the `adminResourceOwnerId[]` list in `config/oauth.ini` and you ran the 
`docs/install.sh` script with the full installation URI of phpvoot as a 
parameter, see above on how to do that.

# Testing

A JavaScript client is included to test the VOOT provider. It uses the 
"implicit" grant type to obtain an access token. The client can  be found at 
`client/index.html`. Modify the client registration and the file 
`client/voot.js` to point to the correct base endpoint as well.

There also is an "authorization code" grant type test client available at 
`web/index.php`.
