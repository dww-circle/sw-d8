#!/bin/sh

drush -y sql-drop
gzcat /Applications/MAMP/htdocs/sw-d8-dumps/sw-d8_dev_2018-05-25T14-32-16_UTC_database.sql.gz | `drush sql-connect`
drush -y updb
drush -y cim
drush -y en sw_migrate
drush cr
