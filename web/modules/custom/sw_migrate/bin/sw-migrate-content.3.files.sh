#!/bin/zsh

# Files
rm -f d6_file.out;
/usr/bin/time drush mim upgrade_d6_file > d6_file.out 2>&1;
