#!/bin/sh

# uci add_list dhcp.@dnsmasq[0].server=[assigned static IP address]

uci commit dhcp
/etc/init.d/dnsmasq restart

echo "DHCP configuration success"
