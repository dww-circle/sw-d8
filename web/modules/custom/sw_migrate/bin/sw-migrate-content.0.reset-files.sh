#!/bin/sh

rm -rf /Applications/MAMP/htdocs/sw-d8/web/sites/default/files
cd /Applications/MAMP/htdocs/sw-d8/web/sites/default
tar -zxvf /Applications/MAMP/htdocs/sw-d8/dumps/sw-d8_dev_2018-03-05T19-46-55_UTC_files.tar.gz
mv files_dev files
find files -type d | xargs chmod 1777
