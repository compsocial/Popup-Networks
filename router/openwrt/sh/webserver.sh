#!/bin/sh

opkg update

sh lighttpd.sh
sh php.sh
sh mysql.sh
#sh php-mysql.sh
sh php-mysqli.sh

# sqlite
#opkg -dest usb install libsqlite3, sqlite3-cli
#opkg -dest usb install php5-mod-sqlite3


# restart lighttpd to enable php plugins
/etc/init.d/lighttpd stop
/etc/init.d/lighttpd start