#!/bin/sh

if [ "$#" -ne 1 ]; then
	echo "Usage: $0 ssid_name" >&2
	exit 1
fi

uci set wireless.radio0.channel=auto
uci set wireless.@wifi-iface[0]=wifi-iface
uci set wireless.@wifi-iface[0].device=radio0
uci set wireless.@wifi-iface[0].network=lan
uci set wireless.@wifi-iface[0].mode=ap
uci set wireless.@wifi-iface[0].ssid="$1"
uci set wireless.@wifi-iface[0].encryption=psk2
uci set wireless.@wifi-iface[0].key=[WiFi_password]
uci set wireless.@wifi-device[0].disabled=0

echo "Wireless SSID is $1 with key [WiFi_password]"

uci set wireless.radio1.channel=36
uci set wireless.@wifi-iface[1]=wifi-iface
uci set wireless.@wifi-iface[1].device=radio1
uci set wireless.@wifi-iface[1].encryption=none
uci set wireless.@wifi-iface[1].ssid=[mesh_5G_ssid]
uci set wireless.@wifi-iface[1].mode=adhoc
uci set wireless.@wifi-iface[1].network=wmesh
uci set wireless.@wifi-device[1].disabled=0

uci commit wireless
wifi

echo "Wireless configuration success"
