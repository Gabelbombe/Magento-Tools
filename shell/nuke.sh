#!/bin/bash
clean()
{
    local a=${1//[^[:alnum:]]/}
    echo "${a,,}"
}

read -p "$(echo -e '\nWould you like to Nuke and Pave? [Y\\n]: ')" confirm
echo;

[ 'y' == "$(clean $confirm)" ] && {

  echo -e "Nuking database...";   mysql -uroot -p magento < /var/www/filson.io/dumps/2014-10-20::21:11:23-configurables-loaded.sql
    [ "$?" -eq 0 ] && {
      echo -e "Flushing temfiles..."; rm -fr /var/www/filson.io/media/tmp/catalog/product/*
      echo -e "Flushing images...";   rm -fr /var/www/filson.io/media/catalog/product/*
    } || {
      echo -e "\nFailed...\n"
      exit 1
    }
  echo -e "\nFinished!\n"
} || {
  echo -e "\nAborting...\n"
}

exit 0