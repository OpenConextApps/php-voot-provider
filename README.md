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

# Apache
Also make sure Apache can read and process the `.htaccess` file by giving it
the appropriate permissions on the (in this case) `/var/www/html/voot` 
directory:

    AllowOverride FileInfo

# Configuration
In the configuration file `config/voot.ini` various aspects can be configured. 
To configure the SAML integration, make sure the following settings are 
correct:

    authenticationMechanism = "SspResourceOwner"

    ; simpleSAMLphp configuration
    [SspResourceOwner]
    sspPath = "/var/simplesamlphp/lib"
    authSource = "default-sp"
    resourceOwnerIdAttributeName = "uid"
    ;resourceOwnerIdAttributeName = "urn:mace:dir:attribute-def:uid"

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

# Configuring OAuth Consumers

The default OAuth token store contains one OAuth consumer. To add your own you
can use the SQLite command line tool to add some:

    $ echo "INSERT INTO Client VALUES('client_id',"Client Description",NULL,'http://host.tld/redirect_uri','public');" | sqlite3 data/oauth2.sqlite

In the future a web application will be written for this.

# Testing

A JavaScript client is included to test the VOOT provider. It uses the 
"implicit grant" OAuth 2 profile to obtain an access token. The client code can 
be found in the `client/vootClient.html`. Modify the client code to point to the
correct OAuth authorization endpoint and API URL.


