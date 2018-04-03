#!/bin/zsh

rm -f 7.redirect_d6.out 7.redirect_csv.out 
/usr/bin/time drush mim upgrade_d6_path_redirect > 7.redirect_d6.out 2>&1
/usr/bin/time drush mim csv_redirect > 7.redirect_csv.out 2>&1
