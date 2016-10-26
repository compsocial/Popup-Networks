#!/bin/sh
# set up file 2
# uhttpd - gui interface
# wireless - wireless ssids
# fstab - usb mount
# olsrd - olsr protocol

sh uhttpd.sh

echo "Make sure the USB key is not plugged in, and the router is connected to the internet. Press any key to continue..."
read dummy_variable

# need internet connection
opkg update

# wireless config
opkg install kmod-ath9k
rm -f /etc/config/wireless; wifi detect > /etc/config/wireless
routername=`uci get system.@system[0].hostname`
sh wireless-config.sh $routername

# mount usb
opkg install kmod-usb2
opkg install kmod-usb-storage block-mount kmod-fs-ext4

echo "Plug in the USB key now. Make sure it is formatted such a way that the swap is the first partition and the storage is the second partition. Press any key to continue..."
read dummy_variable

swapoff /dev/sda1
swapon /dev/sda1
mkdir -p /mnt/shares
mount /dev/sda2 /mnt/shares -o rw,sync

# opkg destination
mv /opt/* /mnt/shares/
rm -rf /opt
ln -s /mnt/shares /opt

# path
sed -i 's,^export PATH=.*,export PATH='"$PATH"':/opt/bin:/opt/sbin:/opt/usr/bin:/opt/usr/sbin,' /etc/profile

# add usb destination to opkg config
sed -i '/dest ram \/tmp/a\dest usb /opt' /etc/opkg.conf

# library path
opkg -dest usb install ldconfig
echo '/opt/usr/lib' >> /etc/ld.so.conf
sed -i '$ a\\/opt\/usr/\lib\/opkg' /etc/ld.so.conf

# add ldconfig to rc.local
sed -i '/exit 0/ i\ldconfig' /etc/rc.local

#git
opkg -dest usb install git
sh git_ssh.sh

# olsrd
opkg install luci luci-ssl pciutils luci-app-olsr luci-app-olsr-services luci-app-olsr-viz olsrd olsrd-mod-arprefresh olsrd-mod-bmf olsrd-mod-dot-draw olsrd-mod-dyn-gw olsrd-mod-dyn-gw-plain olsrd-mod-httpinfo olsrd-mod-jsoninfo olsrd-mod-mdns olsrd-mod-nameservice olsrd-mod-p2pd olsrd-mod-pgraph olsrd-mod-secure olsrd-mod-txtinfo olsrd-mod-watchdog kmod-ipip
/etc/init.d/uhttpd restart

# restart firewall at startup to fix internet sharing issue
sed -i '/exit 0/ i\\/etc\/init.d\/firewall restart' /etc/rc.local

sh fstab-config.sh

ip=`uci get network.wmesh.ipaddr`
sh olsrd-config.sh $ip

sh firewall.sh


# reboot again for sanity
echo "The router will now reboot. Setup is now completed"
reboot