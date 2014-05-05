#!/bin/bash



## ROOT check
if [[ $EUID -ne 0 ]]; then
  echo "This script must be run as su" 1>&2 ; exit 1
fi

## install apache2
apt-get install -y git apache2


 ## if you want Apache2 to start manually on reboot
 ## update-rc.d -f apache2 remove


## version check
apache2 -v


## configure apache2's webroot
chown root:www-data /var/www/html -R
chmod g+s /var/www/html
chmod o-wrx /var/www/html -R


## purge mysql if installed
$(hash mysql) || {
  # remove existing MySQL packages if any
  apt-get purge -y mysql* 

  # remove unwanted packages.
  apt-get autoremove -y
}


## install percona
gpg --keyserver  hkp://keys.gnupg.net --recv-keys 1C4CBDCDCD2EFD2A
gpg -a --export CD2EFD2A | apt-key add -
sh -c 'echo "deb http://repo.percona.com/apt trusty main" >> /etc/apt/sources.list.d/percona.list'

