# PHP VOOT group provider
This project is a stand-alone VOOT group provider. The 
[VOOT specification](https://github.com/fkooman/voot-specification/blob/master/VOOT.md) 
is implemented.

# Features
* HTTP Basic Authentication
* PDO storage backend for VOOT data
* LDAP backend for VOOT

# Requirements
The installation requirements on Fedora/CentOS can be installed like this:

    $ su -c 'yum install php-pdo php php-ldap httpd wget unzip'

On Debian/Ubuntu:

    $ sudo apt-get install sqlite3 php5 php5-sqlite wget unzip php5-ldap

# Installation
The project includes install scripts that downloads the required dependencies
and sets the permissions for the directories to write to and fixes SELinux 
permissions. *NOTE*: in the `chown` line you need to use your own user account 
name!

    $ cd /var/www/html
    $ su -c 'mkdir php-voot-provider'
    $ su -c 'chown fkooman.fkooman php-voot-provider'
    $ git clone git://github.com/fkooman/php-voot-provider.git
    $ cd php-voot-provider

You have to run [Composer](http://getcomposer.org) to install the dependencies:

    $ php /path/to/composer.phar install

Now you can create the default configuration files, the paths will be 
automatically set, permissions set and a sample Apache configuration file will 
be generated and shown on the screen (see later for Apache configuration).

    $ docs/configure.sh

If you want to use VOOT with an SQL database you can also initialize this
database. Make sure you configure it correctly in `config/voot.ini`. If 
you want to use the default SQlite, then you can initialize immediately:

    $ php docs/initVootDatabase.php

On Ubuntu (Debian) you would typically install in `/var/www/php-voot-provider` 
and not in `/var/www/html/php-voot-provider` and you use `sudo` instead of 
`su -c`.

# SELinux
The install script already takes care of setting the file permissions of the
`data/` directory to allow Apache to write to the directory. However, if you
want to use the LDAP backend to retrieve group information Apache also needs
the permission to access LDAP servers.

    $ sudo setsebool -P httpd_can_connect_ldap=on

This is only for Red Hat based Linux distributions like RHEL, CentOS and 
Fedora.

# Apache
There is an example configuration file in `docs/apache.conf`. 

On Red Hat based distributions the file can be placed in 
`/etc/httpd/conf.d/php-voot-provider.conf`. On Debian based distributions the 
file can be placed in `/etc/apache2/conf.d/php-voot-provider`. Be sure to 
modify it to suit your environment and do not forget to restart Apache. 

The install script from the previous section outputs a config for your system
which replaces the `/PATH/TO/APP` with the actual directory.

# Configuration
In the configuration file `config/voot.ini` various aspects can be configured. 

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
`BackendTest.php` script in the `docs/` directory. First configure LDAP in 
`config/voot.ini` and then run the script like this:

    $ php docs/BackendTest.php fkooman

This should return an `array` with the group information. If it does not work,
make sure you match the configuration values with the `ldapsearch` commands 
that do work.
