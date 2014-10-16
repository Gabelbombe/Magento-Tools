#!/bin/bash
# CPR : Jd Daniel :: Ehime-ken
# MOD : 2014-10-16 @ 12:24:27
# VER : 1.0
#
# Quickly alter the DNS record of Magento, works on CE and EE all versions

read -p 'New domain name: ' domain
read -p 'Database to use: ' database
read -p 'Table prefix:    ' prefix

mysql -u root -p -e "
    USE ${database};
    UPDATE ${prefix}core_config_data set value='http://${domain}/' WHERE path='web/unsecure/base_url';
    UPDATE ${prefix}core_config_data set value='https://${domain}/' WHERE path='web/secure/base_url';
"