#!/bin/sh

if [ "$#" -ne 1 ]; then
	echo "Usage: $0 router_ip_address" >&2
	exit 1
fi

cat /dev/null > /etc/config/olsrd

uci add olsrd olsrd
uci set olsrd.@olsrd[0]=olsrd
uci set olsrd.@olsrd[0].IpVersion=4
uci set olsrd.@olsrd[0].MainIp=$1

uci add olsrd Interface
uci set olsrd.@Interface[0]=Interface
uci set olsrd.@Interface[0].ignore=0
uci set olsrd.@Interface[0].interface=wmesh
uci set olsrd.@Interface[0].Mode=mesh

uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_arprefresh.so.0.1
uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_dyn_gw.so.0.5
uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_httpinfo.so.0.1
uci set olsrd.@LoadPlugin[-1].port=1978
uci set olsrd.@LoadPlugin[-1].Net='0.0.0.0 0.0.0.0'
uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_nameservice.so.0.3
uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_txtinfo.so.0.1
uci set olsrd.@LoadPlugin[-1].accept=0.0.0.0
uci add olsrd LoadPlugin
uci set olsrd.@LoadPlugin[-1]=LoadPlugin
uci set olsrd.@LoadPlugin[-1].library=olsrd_jsoninfo.so.0.0

uci commit olsrd
/etc/init.d/olsrd restart

echo "OLSR configuration success"
