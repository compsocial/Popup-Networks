#!/bin/sh
#takes one argument/parameter: the name of the package which didn't install correctly and should be removed along with its dependencies
#do opkg update first
#example: ./opkgremovepartlyinstalledpackage.sh pulseaudio-daemon

#get list of all packages that would be installed along with package x
opkg update
PACKAGES=`opkg --force-space --noaction install $1 | grep http | cut -f 2 -d ' ' | sed 's/.$//'`
for i in $PACKAGES
do
        LIST=`wget -qO- $i | tar -Oxz ./data.tar.gz | tar -tz | sort -r | sed 's/^./\/overlay/'`
        for f in $LIST
        do
                if [ -f $f ]
                then
                        echo "Removing file $f"
                        rm -f $f
                fi
                if [ -d $f ]
                then
                        echo "Try to remove directory $f (will only work on empty directories)"
                        rmdir $f
                fi
        done
done
echo "You may need to reboot for the free space to become visible"