#!/bin/zsh

rm -f 7.url_alias.out
/usr/bin/time drush mim upgrade_d6_url_alias > 7.url_alias.out 2>&1
