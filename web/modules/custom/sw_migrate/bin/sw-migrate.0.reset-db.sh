#!/bin/sh

drush -y sql-drop
gzcat /Applications/MAMP/htdocs/sw-d8/dumps/2018-02-18.dev-repaired.2.sql.gz | `drush sql-connect`
drush -y updb
drush -y cim
drush cr
