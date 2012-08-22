#!/bin/sh
for i in `find . | grep \.php$`
do
        php -l $i > /dev/null
done

for i in `find lib/ | grep \.php$`
do
	php $i
done

