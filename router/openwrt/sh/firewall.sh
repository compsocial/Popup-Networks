sed -i '$ a\# OLSR needs port 698 to transmit state messages.' /etc/firewall.user
sed -i '$ a\iptables -A input_rule -p udp --dport 698 -j ACCEPT' /etc/firewall.user
sed -i '$ a\\' /etc/firewall.user
sed -i '$ a\# For WIFI clients to connect to nodes.' /etc/firewall.user
sed -i '$ a\iptables -A forwarding_rule -i wlan1 -o wlan1 -j ACCEPT' /etc/firewall.user