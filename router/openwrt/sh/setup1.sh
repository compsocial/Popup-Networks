#!/bin/sh
# set up file 1
# system - router name, timezone
# network - network interfaces, clone mac address
# dhcp - set dns

echo "What's the router number? (1-255)"
read routernumber

case $routernumber in
	[1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-4])	echo 'The router name will be "ssid_'$routernumber'" with ip address 192.168.1.'$routernumber''
				;;
	*)			echo 'The input is not a number or out of range'
				exit 1 
				;;
esac

routername="ssid_$routernumber"
ip="192.168.1.1"
mesh_ip="192.168.2.$routernumber"

sh system-config.sh $routername
sh dhcp-config.sh
sh network-config.sh $ip $mesh_ip

# reboot for sanity
echo "The router will now reboot. Run setup2.sh after reboot"
reboot
