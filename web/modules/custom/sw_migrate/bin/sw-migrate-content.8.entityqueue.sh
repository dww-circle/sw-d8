#!/bin/sh

drush mim csv_entityqueue_storyqueues
drush scr ./sw-migrate-content.8.entityqueue-clear.php
drush scr ./sw-migrate-content.8.entityqueue-items.php
drush scr ./sw-migrate-content.8.entityqueue-items.php sw3-entityqueue-storyqueue-items story_queues
