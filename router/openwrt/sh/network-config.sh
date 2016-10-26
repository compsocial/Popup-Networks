#!/bin/sh

if [ "$#" -ne 2 ]; then
	echo "Usage: $0 router_ip_address mesh_network_ip_address" >&2
	exit 1
fi

uci set network.lan.ifname=eth0.1
uci set network.lan.type=bridge
uci set network.lan.proto=static
uci set network.lan.netmask=255.255.255.0
uci set network.lan.ipaddr="$1"

uci set network.wan.ifname=eth1
uci set network.wan.proto=dhcp
# uci set network.wan.macaddr=[MAC address to clone]

uci set network.wmesh=interface
uci set network.wmesh.proto=static
uci add_list network.wmesh.ipaddr="$2"
uci add_list network.wmesh.netmask=255.255.255.0

uci commit network
#/etc/init.d/network restart

echo "Network configuration success"
