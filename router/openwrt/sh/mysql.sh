#!/bin/sh

# mysql
opkg -dest usb install mysql-server

sed -i 's,^datadir.*,datadir\t\t= /opt/data/mysql,g' /opt/etc/my.cnf
sed -i 's,^tmpdir.*,tmpdir\t\t= /tmp,g' /opt/etc/my.cnf
sed -i 's,^basedir.*,basedir\t\t= /opt/usr,g' /opt/etc/my.cnf
#sed -i 's,^socket.*,socket\t\t= /var/run/mysqld.sock,g' /opt/etc/my.cnf

mkdir -p /opt/data/mysql
ln -s /opt/etc/my.cnf /etc/my.cnf
ln -s /opt/etc/init.d/mysqld /etc/init.d/mysqld

sed -i 's, /usr/bin/mysqld, /opt/usr/bin/mysqld,g' /opt/etc/init.d/mysqld

ldconfig
mysql_install_db --force --basedir=/opt/usr

/etc/init.d/mysqld start
/etc/init.d/mysqld enable

printf 'Set MySQL password with command\n$ mysqladmin -u root password "[mysql_password]"\n'
# mysqladmin -u root password '[mysql_password]'
