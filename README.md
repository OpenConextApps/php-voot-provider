# PHP VOOT group provider

This project is a stand-alone VOOT group provider. The 
[VOOT specification](http://www.openvoot.org/) is implemented.

# Features
* PDO storage backend for VOOT data and OAuth tokens
* LDAP backend for VOOT
* OAuth 2 support (implicit grant only for now)
* SAML authentication support ([simpleSAMLphp](http://www.simplesamlphp.org)) 
* BrowserID support (almost)

# Installation
The project includes an install script that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions.

    $ mkdir /var/www/html/voot
    $ git clone git://github.com/fkooman/phpvoot.git /var/www/html/voot
    $ cd /var/www/html/voot
    $ docs/install.sh

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
In the configuration file `config/voot.ini` and `config/oauth.ini` various 
aspects can be configured. To configure the SAML integration (in `oauth.ini`), 
make sure the following settings are correct:

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

# Configuring OAuth Clients

The default OAuth token store contains two OAuth clients, one for the included
demo VOOT client (`client/vootClient.html` and for the management environment 
(`manage/index.html`). You may need to update the `redirect_uri` to have them
point to your actual server. By default they point to `http://localhost/voot/`:

    $ echo "UPDATE Client SET redirect_uri='https://www.example.com/voot/client/vootClient.html' WHERE id='voot';" | sqlite3 data/oauth2.sqlite
    $ echo "UPDATE Client SET redirect_uri='https://www.example.com/voot/manage/index.html' WHERE id='manage';" | sqlite3 data/oauth2.sqlite

For now you also need to modify the endpoint in the `manage/manage.js` file, 
this is currently stored in the source code... 

Once this is done you can manage the OAuth client registrations by going to the
URL configured above at `https://www.example.com/voot/manage/index.html`. Make
sure the user identifiers you want to allow `admin` permissions are listed in 
the `adminResourceOwnerId[]` list in `config/oauth.ini`.

# Testing

A JavaScript client is included to test the VOOT provider. It uses the 
"implicit grant" OAuth 2 profile to obtain an access token. The client code can 
be found in the `client/vootClient.html`. Modify the client code to point to the
correct OAuth authorization endpoint and API URL.

# Revoking Access
An endpoint is defined on the OAuth authorization server that can be used
by the user to revoke authorization to clients at `/oauth/revoke`.

