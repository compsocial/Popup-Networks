#!/bin/sh

if [ "$#" -ne 1 ]; then
	echo "Usage: $0 router_name" >&2
	exit 1
fi

#filename=/etc/config/system
#if [ ! -e $filename ];  then
#	echo "$filename not exists!" >&2
#	exit 1
#fi

#if grep -q "option timezone" "$filename";
#then
#	sed -i -e "/option timezone.*/ s/option timezone.*/option timezone 'EST5EDT,M3.2.0,M11.1.0'/" "$filename"
#else
#	sed -i -e "/config system/ a\option timezone 'EST5EDT,M3.2.0,M11.1.0'" "$filename"
#	sed -i -e "s/^option timezone 'EST5EDT,M3.2.0,M11.1.0'/\toption timezone 'EST5EDT,M3.2.0,M11.1.0'/g" "$filename"
#fi

#if grep -q "option zonename" "$filename";
#then
#	sed -i -e "/option zonename.*/ s,&,option zonename 'America/New York'," "$filename"
#else
#	sed -i -e "/config system/ a\option zonename 'America/New York'" "$filename"
#	sed -i -e "s,^option zonename 'America/New York',\toption zonename 'America/New York',g" "$filename"
#fi

#if grep -q "option hostname" "$filename";
#then
#	sed -i -e "/option hostname.*/ s/option hostname.*/option hostname $1/g" "$filename"
#else
#	sed -i -e "/config system/ a\option hostname $1" "$filename"
#	sed -i -e "s/^option hostname $1/\toption hostname $1/g" "$filename"
#fi

uci set system.@system[0].hostname="$1"
uci set system.@system[0].zonename='America/New York'
uci set system.@system[0].timezone='EST5EDT,M3.2.0,M11.1.0'

uci commit system

echo "System configuration success"