#!/bin/sh

# User settings
drush mim upgrade_d6_user_settings
drush mim upgrade_d6_user_mail

# Roles
drush mim upgrade_d6_user_role
