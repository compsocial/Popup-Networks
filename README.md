# Popup Networks
System code for Popup Networks accompanying *Popup Networks: Creating Decentralized Social Media on Top of Commodity Wireless Routers* published at GROUP'16

## Equipment
1. A wireless router with a USB port, dual radio band (2.4 GHz and 5 GHz,) and supports OpenWrt (check <http://wiki.openwrt.org/toh/start> for supported device). We used Asus DIR-825.
2. A USB flash drive with at least 2GB capacity (8GB recommended)
3. An Ethernet cable
4. Internet connection

## Configuration
Inside router/openwrt/sh directory, there are a few scripts that you will need to alter to configure your network:
1. *wireless-config.sh _(required)_*: 
  1. Specify the password for your WiFi connection by replacing *[WiFi_password]* with your password. 
  1. Specify the SSID for your mesh network by replacing *[mesh_5G_ssid]* with your preferred name.
1. *mysql.sh _(required)_*: Specify the root password for MySQL by uncommenting line 24 and replace *[mysql_password]* with your MySQL password.
1. *dhcp-config.sh (optional)*: If your network requires your router to use a specific IP address, uncomment line 3 and replace *[assigned static IP address]* with your IP address.
1. *network-config.sh (optional)*: If your network requires your router to have a specific MAC address (MAC address clone), uncomment line 16 and replace *[MAC address to clone]* with your MAC address.
1. *setup1.sh (optional)*: If you want to change the format of your WiFi SSID, replace *ssid_$routernumber* with your preferred SSID.

## Pre-Setup
1. Format the USB flash drive to use with OpenWrt by using the util/format_usb.sh script or follow instructions on <https://wiki.openwrt.org/doc/howto/storage>
2. Flash OpenWrt on your router, following instructions on <https://wiki.openwrt.org/doc/start>

## Popup Networks Setup
1. Run setup1.sh, allow the router to restart
2. Run setup2.sh