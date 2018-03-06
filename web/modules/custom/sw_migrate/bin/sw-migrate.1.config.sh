#!/bin/sh

# Run initial migrations that have no other dependencies.

# Settings / configuration
drush mim upgrade_d6_filter_format
drush mim upgrade_d6_system_cron
drush mim upgrade_d6_system_file
drush mim upgrade_system_image
drush mim upgrade_system_logging
