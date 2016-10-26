#!/bin/sh

uci set fstab.automount=global
uci set fstab.automount.from_fstab=1
uci set fstab.automount.anon_mount=1
uci set fstab.autoswap=global
uci set fstab.autoswap.from_fstab=1
uci set fstab.autoswap.anon_swap=0
uci set fstab.@mount[0]=mount
uci set fstab.@mount[0].target=/mnt/shares
uci set fstab.@mount[0].device=/dev/sda2
uci set fstab.@mount[0].options=rw,sync
uci set fstab.@mount[0].enabled=1
uci set fstab.@mount[0].enabled_fsck=0
uci set fstab.@swap[0]=swap
uci set fstab.@swap[0].device=/dev/sda1
uci set fstab.@swap[0].enabled=1

uci commit fstab
#/etc/init.d/fstab restart

echo "Fstab configuration success"