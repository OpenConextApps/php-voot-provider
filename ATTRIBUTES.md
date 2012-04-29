# Extended Attributes

In order to store a file to the file system it makes a lot of sense to keep
track of the MIME type of the file. The MIME type can be extracted from the 
Content-Type header that is specified when the file is being uploaded by 
the browser.

An alternative to explicitly storing this MIME type it is possible to use 
"guessing", but this is not so accurate, for example it is does not work
for JSON files, they are seen as plain text by the "file" command:

    $ cat data.json 
    {"key":["value1","value2"],"foo":"bar"}

    $ file data.json 
    data.json: ASCII text

So in order to fix this, extended attributes can be used to store this 
information explicitly:

    $ setfattr -n "user.mime_type" -v "application/json" data.json

And then retrieve it:

    $ getfattr -n "user.mime_type" data.json

This functionality can be used from PHP as well, using the `xattr_set` and
`xattr_get` functions.

The common attribute names are specified in a FreeDesktop document at 
http://www.freedesktop.org/wiki/CommonExtendedAttributes. 

In order to create a backup of the files *including* their attributes `tar` 
can be used to also retain the extended attributes:

    $ tar --xattr -cf archive.tar data.json

Now the PHP example:

    <?php
        echo xattr_get("data.json", "mime_type");
    ?>

This does require the PECL extension xattr from 
http://pecl.php.net/package/xattr.

