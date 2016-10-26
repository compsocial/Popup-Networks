#!/bin/sh

uci set uhttpd.main.listen_http=0.0.0.0:8080
uci commit uhttpd

echo "$(uci get network.lan.ipaddr) router" >> /etc/hosts 

/etc/init.d/uhttpd enable
/etc/init.d/uhttpd restart