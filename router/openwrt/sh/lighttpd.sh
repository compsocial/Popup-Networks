#!/bin/sh

# lighttpd
opkg -dest usb install lighttpd
opkg -dest usb install lighttpd-mod-fastcgi
opkg -dest usb install lighttpd-mod-simple-vhost
opkg -dest usb install lighttpd-mod-rewrite
ln -s /opt/etc/init.d/lighttpd /etc/init.d/lighttpd
sed -i '$ a\\/opt\/usr/\lib\/lighttpd' /etc/ld.so.conf
ldconfig

# lighttpd startup script
sed -i '\_ /var/log/lighttpd_ s_/var_/opt/var_g' /opt/etc/init.d/lighttpd
sed -i '\_ /usr/sbin/lighttpd_ s_/usr_/opt/usr_g' /opt/etc/init.d/lighttpd
sed -i '\_ /etc/lighttpd/lighttpd.conf_ s_/etc_/opt/etc_g' /opt/etc/init.d/lighttpd

# lighttpd config
sed -i 's/#[ \t]*server.modules/server.modules/' /opt/etc/lighttpd/lighttpd.conf
sed -i 's/#[ \t]*"mod_fastcgi"/\t"mod_fastcgi"/' /opt/etc/lighttpd/lighttpd.conf
sed -i 's/#[ \t]*"mod_simple_vhost"/\t"mod_simple_vhost"/' /opt/etc/lighttpd/lighttpd.conf
sed -i 's/#[ \t]*"mod_rewrite"/\t"mod_rewrite"/' /opt/etc/lighttpd/lighttpd.conf
sed -e '1,/#[ \t]*)/s/#[ \t]*)/)/' < /opt/etc/lighttpd/lighttpd.conf > /opt/etc/lighttpd/lighttpd.conf.new
mv /opt/etc/lighttpd/lighttpd.conf.new /opt/etc/lighttpd/lighttpd.conf

sed -i 's/index-file.names.*/index-file.names = ( "index.html", "default.html", "index.htm", "default.htm" , "index.php" )/g' /opt/etc/lighttpd/lighttpd.conf
sed -i 's,^server.document-root.*,server.document-root = "/opt/www",g' /opt/etc/lighttpd/lighttpd.conf
#sed -i 's,#*server.port.*,server.port = 81,g' /opt/etc/lighttpd/lighttpd.conf

sed -i '$ a\fastcgi.server = (".php" => ((' /opt/etc/lighttpd/lighttpd.conf
sed -i '$ a\    "bin-path" => "/opt/usr/bin/php-cgi",' /opt/etc/lighttpd/lighttpd.conf
sed -i '$ a\    "socket" => "/tmp/php.socket"' /opt/etc/lighttpd/lighttpd.conf
sed -i '$ a\)))' /opt/etc/lighttpd/lighttpd.conf
sed -i '$ a\\' /opt/etc/lighttpd/lighttpd.conf
sed -i '$ a\url.rewrite-if-not-file = ("api/(.*)" => "/api/api.php/$0")' /opt/etc/lighttpd/lighttpd.conf


# make www folder
mkdir /opt/www

# start service
/etc/init.d/lighttpd start
/etc/init.d/lighttpd enable