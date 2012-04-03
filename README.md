# PHP VOOT group provider

This project is a tiny stand-alone VOOT group provider. The 
[VOOT specification](http://www.openvoot.org/) is implemented.

# Features
* PDO storage backend for VOOT data and OAuth tokens
* LDAP backend for VOOT
* OAuth 2 support (implicit grant only for now)
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

# SELinux
The install script already takes care of setting the file permissions of the
`data/` directory to allow Apache to write to the directory. However, if you
want to use the LDAP backend to retrieve group information Apache also needs
the permission to access LDAP servers. This permission can be given by using
`setsebool` as root:

    $ sudo setsebool -P httpd_can_connect_ldap=on

# Configuration
In the configuration file `config/voot.ini` various aspects can be configured. 
To configure the SAML integration, make sure the following settings are 
correct:

    authenticationMechanism = "SspResourceOwner"

    [oauthSsp]
    sspPath = '/var/simplesamlphp/lib'
    authSource = 'default-sp'
    resourceOwnerIdAttributeName = 'uid'

## LDAP 
It is possible to use an LDAP server as backend to retrieve group membership.
It depends on your LDAP server configuration how to configure this. It is 
always helpful to start out with some `ldapsearch` commands to see what will 
work for your setup. Below is an example based on searching for `uid`:

    $ ldapsearch -H ldap://localhost -b 'ou=Groups,dc=wind,dc=surfnet,dc=nl' -x '(uniqueMember=uid=fkooman,ou=People,dc=wind,dc=surfnet,dc=nl)' cn

This is an example for a search based on `cn`:

    $ ldapsearch -H ldap://directory.surfnet.nl -b 'ou=Groups,ou=Office,dc=surfnet,dc=nl' -x '(uniqueMember=cn=Francois Kooman,ou=Persons,ou=Office,dc=surfnet,dc=nl)' cn

An example for Microsoft Active Directory (needs LDAP bind):

    $ ldapsearch -H ldap://adfs-sp.aai.surfnet.nl -b 'cn=Users,dc=demo,dc=sharepoint,dc=aai,dc=surfnet,dc=nl' -D 'cn=Administrator,cn=Users,dc=demo,dc=sharepoint,dc=aai,dc=surfnet,dc=nl' -w secret "(samAccountName=fkooman)" memberOf

This can be configured in `config/voot.ini` as well.

# Configuring OAuth Consumers

The default OAuth token store contains one OAuth consumer. To add your own you
can use the SQLite command line tool to add some:

    $ echo "INSERT INTO Client VALUES('client_id',NULL,'http://host.tld/redirect_uri','public');" | sqlite3 data/oauth2.sqlite

# Testing

A JavaScript client is included to test the VOOT provider. It uses the 
"implicit grant" OAuth 2 profile to obtain an access token. The client code can 
be found in the `client/vootClient.html`. Modify the client code to point to the
correct OAuth authorization endpoint and API URL.


