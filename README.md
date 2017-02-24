# PHP VOOT group provider
This project is a stand-alone VOOT group provider. The 
[VOOT specification](http://openvoot.org/) 
is implemented.

# Community supported

Please note that this software is provided by SURFnet to the community as-is.
Questions, bug reports and PR's are welcome, but will be attended to on a best-effort
basis.

# Features
* HTTP Basic Authentication
* PDO storage backend for VOOT data
* LDAP backend for VOOT data

# Requirements
If you want to have LDAP support you need to have the PHP LDAP extension 
installed, `yum install php-ldap` (Fedora) or `apt-get install php5-ldap` 
(Debian). If you want to have database support you need to have the PDO 
extension and the relevant platform drivers installed, `yum install php-pdo` 
(Fedora) or `apt-get install php5-sqlite` (Debian).

# Installation
We assume you want to install in `/var/www/html/php-voot-provider` and that 
you want to access the service through `http://localhost/php-voot-provider`. In
real deployments you of course want to use a TLS certificate.

Below are instructions to install a release or from Git. They assume you have
`root` permissions, but of course you can also create a directory under your
web server directory root with user writable permissions and run the commands
as a normal user.

## From Git
You need [Composer](http://getcomposer.org) to install the dependencies.

    # cd /var/www/html
    # git clone https://github.com/fkooman/php-voot-provider.git
    # cd php-voot-provider
    # php /path/to/composer.phar install
    
Now you can copy the default configuration file in `config/voot.ini.defaults` 
to `config/voot.ini` and modify it for your setup. The various configuration 
fields are explained. If you configure LDAP you do not need to do anything 
else, for the database setup see the instructions below. If you use a system 
with SELinux you may need to give it permission to connect to LDAP servers:

    # setsebool -P httpd_can_connect_ldap=on

## From Release
If you download a release you do not need to run 
[Composer](http://getcomposer.org) yourself as all the dependencies are already
included. You can just extract the release in `/var/www/html/php-voot-provider`
and continue.

## Apache
You need to install a little Apache configuration snippet to point to the `web`
directory inside `php-voot-provider` as that is where the script that provides
the REST service is located.

    Alias /php-voot-provider /var/www/html/php-voot-provider/web/voot.php

    <Directory "/var/www/html/php-voot-provider/web">
        AllowOverride None
        Options None
    </Directory>

You can place this in `/etc/httpd/conf.d/php-voot-provider.conf` on Fedora, or
`/etc/apache2/conf.d/php-voot-provider.conf` on Debian.

# Configuration
You can configure both a database or a LDAP as a backend.

## Database
The database schema can be found in `schema/db.sql`. Import this into your
database. SQlite3 and MySQL were tested. 

You can add some additional users for testing using:

    # php bin/addUsers.php

This will add some users and groups and membership information to the database.
Make sure your web server can read this file. It is assumed that your database
connection is setup correctly in `config/voot.ini`.

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

This can all be configured in `config/voot.ini`, see the examples there for 
more information.

# Testing User/Group Backend
To test the configuration of your LDAP/Database settings it is possible to use 
the `BackendTest.php` script in the `bin/` directory. First configure LDAP or
the database in `config/voot.ini` and then run the script like this:

    $ php bin/BackendTest.php fkooman

This should return an `array` with the group information. If it does not work,
make sure you match the configuration values with the `ldapsearch` commands 
that do work.

# Testing REST API
You can then try to use the REST API with e.g. cURL:

    $ curl http://localhost/php-voot-provider/groups/fkooman

This should return some JSON data with the group membership information. For 
querying group members you can use the following call:

    $ curl http://localhost/php-voot-provider/people/fkooman/members

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
