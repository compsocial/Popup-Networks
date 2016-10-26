#!/bin/sh

# mysql php plugin
opkg -dest usb install php5-mod-mysql

sed -i 's,^;extension=mysql.so,extension=mysql.so,g' /opt/etc/php.ini
sed -i 's,^mysql.default_socket.*,mysql.default_socket = /var/run/mysqld.sock,g' /opt/etc/php.ini