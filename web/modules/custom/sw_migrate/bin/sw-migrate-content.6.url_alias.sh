#!/bin/zsh

rm -f 6.url_alias.out
/usr/bin/time drush mim upgrade_d6_url_alias > 6.url_alias.out 2>&1
