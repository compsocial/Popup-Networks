# Generate your identity key on openwrt
dropbearkey -t rsa -f ~/.ssh/id_rsa

# Convert public key from dropbear binary to openssh text
# Copy and paste output from below to bitbucket account ssh keys
dropbearkey -y -f ~/.ssh/id_rsa | head -n 2 | tail -1 > ~/.ssh/id_rsa.pub

# Change git ssh command
echo "#!/bin/sh" > ~/.gitssh.sh
echo "dbclient -y -i ~/.ssh/id_rsa \$*" >> ~/.gitssh.sh
chmod +x ~/.gitssh.sh
echo "export GIT_SSH=\$HOME/.gitssh.sh" >> /etc/profile

# Now login again to openwrt