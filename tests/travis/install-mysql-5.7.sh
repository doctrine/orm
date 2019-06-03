#!/usr/bin/env bash

set -ex

echo "Installing MySQL 5.7..."

sudo service mysql stop
sudo apt-get remove "^mysql.*"
sudo apt-get autoremove
sudo apt-get autoclean
sudo apt-key adv --keyserver pgp.mit.edu --recv-keys A4A9406876FCBD3C456770C88C718D3B5072E1F5
echo mysql-apt-config mysql-apt-config/select-server select mysql-5.7 | sudo debconf-set-selections
wget http://dev.mysql.com/get/mysql-apt-config_0.8.6-1_all.deb
sudo DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config_0.8.6-1_all.deb
sudo rm -rf /var/lib/apt/lists/*
sudo apt-get clean
sudo apt-get update -q
sudo apt-get install -q -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" mysql-server libmysqlclient-dev
sudo mysql_upgrade

echo "Restart mysql..."
sudo mysql -e "use mysql; update user set authentication_string=PASSWORD('') where User='root'; update user set plugin='mysql_native_password';FLUSH PRIVILEGES;"

