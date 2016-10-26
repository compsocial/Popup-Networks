#!/bin/sh
# to run on linux

echo "Make sure that you know your root password."
echo "Make sure to unmount the usb drive before continuing."
echo "fdisk will now run. Take note of device mount point (/dev/sd?) for the usb drive. Press any key to continue"
read dummy

sudo fdisk -l
echo "What is the mount point for the usb drive? (/dev/sd?)"
while read mp; do
	case $mp in
		/dev/sd[a-z])	break
						;;
		[a-z])			mp=/dev/sd"$mp"
						break
						;;
		*)				echo "Invalid mount point. Try again!"
						;;
	esac
done

echo "Are you sure you want to format \"$mp\"? (y/N)"
echo yesno
case $yesno in
	y|Y|yes|Yes|YES)	;;
	*)					exit 0
esac 

swapspace=32

(
echo d;
echo 1;
echo d;
echo 2;
echo d;
echo 3;
echo d;
echo 4;
echo n;
echo p;
echo 1;
echo ;
echo '+'$swapspace'M';
echo n;
echo p;
echo 2;
echo ;
echo ;
echo t;
echo 1;
echo 82;
echo p;
echo w;
) | sudo fdisk "$mp"

sudo mkswap ''$mp'1'
sudo mke2fs -m 1 -L USB ''$mp'2'
