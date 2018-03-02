#!/bin/sh


# Node types
drush mim upgrade_d6_node_type
drush mim upgrade_d6_view_modes

# Fields
drush mim upgrade_d6_field
drush mim upgrade_d6_field_instance
drush mim upgrade_d6_field_formatter_settings
drush mim upgrade_d6_field_instance_widget_settings

# Upload fields + instances
drush mim upgrade_d6_upload_field
drush mim upgrade_d6_upload_field_instance
drush mim upgrade_d6_upload_entity_display
drush mim upgrade_d6_upload_entity_form_display

# Other node settings
drush mim upgrade_d6_node_settings
drush mim upgrade_d6_node_setting_promote
drush mim upgrade_d6_node_setting_status
drush mim upgrade_d6_node_setting_sticky
