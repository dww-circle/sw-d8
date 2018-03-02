#!/bin/sh

# Vocabularies
drush mim upgrade_d6_taxonomy_vocabulary

# Term reference fields
drush mim upgrade_d6_vocabulary_field
drush mim upgrade_d6_vocabulary_field_instance
drush mim upgrade_d6_vocabulary_entity_display
drush mim upgrade_d6_vocabulary_entity_form_display

# Terms
drush mim upgrade_d6_taxonomy_term --feedback=500
