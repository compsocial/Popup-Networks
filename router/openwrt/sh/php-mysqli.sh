#!/bin/sh

# mysqli php plugin
opkg -dest usb install php5-mod-mysqli

sed -i '$ a\
\
extension=mysqli.so\
\
[MySQLi]\
mysqli.allow_local_infile = On\
mysqli.allow_persistent = On\
mysqli.cache_size = 2000\
mysqli.max_persistent = -1\
mysqli.max_links = -1\
mysqli.default_port = \
mysqli.default_socket = /var/run/mysqld.sock\
mysqli.default_host = \
mysqli.default_user = \
mysqli.default_password = \
mysqli.connect_timeout = 60\
mysqli.trace_mode = Off' /opt/etc/php.ini