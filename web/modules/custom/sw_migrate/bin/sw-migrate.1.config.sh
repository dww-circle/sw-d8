#!/bin/sh

# Run initial migrations that have no other dependencies.

# Settings / configuration
drush mim upgrade_d6_filter_format
drush mim upgrade_d6_dblog_settings
drush mim upgrade_d6_search_settings
drush mim upgrade_d6_system_cron
drush mim upgrade_d6_system_date
drush mim upgrade_d6_system_file
drush mim upgrade_d6_system_performance
drush mim upgrade_search_page
drush mim upgrade_system_image
drush mim upgrade_system_logging
drush mim upgrade_system_site
drush mim upgrade_taxonomy_settings
drush mim upgrade_text_settings
