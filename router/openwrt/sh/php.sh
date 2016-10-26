#!/bin/sh

# php-fcgi
opkg -dest usb install php5-fastcgi
opkg -dest usb install php5-mod-gd
opkg -dest usb install php5-mod-json
opkg -dest usb install php5-mod-curl
opkg -dest usb install php5-mod-mcrypt
opkg -dest usb install php5-mod-session
opkg install zoneinfo-core zoneinfo-northamerica
ln -s /opt/etc/php.ini /etc/php.ini
ln -s /opt/etc/init.d/php5-fastcgi /etc/init.d/php5-fastcgi
sed -i '$ a\\/opt\/usr/\lib\/php' /etc/ld.so.conf
ldconfig

# php5-fastcgi
sed -i 's, /usr/bin/php-fcgi, /opt/usr/bin/php-fcgi,g' /opt/etc/init.d/php5-fastcgi

# php.ini
sed -i 's,^doc_root.*,doc_root = "/opt/www",g' /opt/etc/php.ini
sed -i 's,^extension_dir.*,extension_dir = "/opt/usr/lib/php",g' /opt/etc/php.ini
sed -i 's,^;extension=curl.so,extension=curl.so,g' /opt/etc/php.ini
sed -i 's,^;extension=gd.so,extension=gd.so,g' /opt/etc/php.ini
sed -i 's,^;extension=json.so,extension=json.so,g' /opt/etc/php.ini
sed -i 's,^;extension=mcrypt.so,extension=mcrypt.so,g' /opt/etc/php.ini
sed -i 's,^;extension=session.so,extension=session.so,g' /opt/etc/php.ini
sed -i 's,^[;]*date.timezone.*,date.timezone = "America/New_York",g' /opt/etc/php.ini
