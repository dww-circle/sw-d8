#!/bin/zsh

rm -f 7.redirect.out
/usr/bin/time drush mim upgrade_d6_path_redirect > 7.redirect.out 2>&1
