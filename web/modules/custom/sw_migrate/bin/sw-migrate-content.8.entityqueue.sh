#!/bin/sh

drush scr ./sw-migrate-content.8.entityqueue-clear.php
drush scr ./sw-migrate-content.8.entityqueue-items.php
