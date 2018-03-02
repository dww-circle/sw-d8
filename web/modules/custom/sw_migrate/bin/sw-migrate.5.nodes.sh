#!/bin/sh

# Actual nodes
drush mim upgrade_d6_node:page
drush mim upgrade_d6_node:blog_couldnt_make_it_up
drush mim upgrade_d6_node:blog_critical_reading --feedback=100
drush mim upgrade_d6_node:edition  # Don't think we're migrating these (?)
drush mim upgrade_d6_node:image    # Ugh, need to be converted to media entities
drush mim upgrade_d6_node:insert_box --feedback=100
drush mim upgrade_d6_node:person --feedback=100

# Needs all sorts of help:
# - Remapping from image nodes to image media entities
# - Convert DME tags
# - Set main image field
# - Populate address fields (from taxonomy, whoops)
# - Convert contributors from taxonomy to person nodes (?)
drush mim upgrade_d6_node:story --feedback=100
