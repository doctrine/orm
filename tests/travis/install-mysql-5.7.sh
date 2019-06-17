#!/usr/bin/env bash

set -ex

echo "Restart mysql..."
sudo mysql -e "use mysql; update user set authentication_string=PASSWORD('') where User='root'; update user set plugin='mysql_native_password';FLUSH PRIVILEGES;"
sudo mysql_upgrade -u root
sudo service mysql restart
