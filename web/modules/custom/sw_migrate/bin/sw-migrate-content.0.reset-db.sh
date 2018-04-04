#!/bin/sh

drush -y sql-drop
gzcat /Applications/MAMP/htdocs/sw-d8-dumps/2018-04-04.migrate-clean-start.sql.gz | `drush sql-connect`
drush -y updb
drush -y cim
drush -y en sw_migrate
drush cr
