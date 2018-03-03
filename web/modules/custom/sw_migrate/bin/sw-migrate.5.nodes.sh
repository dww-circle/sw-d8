#!/bin/sh

# Actual nodes
drush mim upgrade_d6_node_page
drush mim upgrade_d6_node_blog_couldnt_make_it_up
drush mim upgrade_d6_node_blog_critical_reading
#drush mim upgrade_d6_node_edition  # Don't think we're migrating these (?)
#drush mim upgrade_d6_node_image    # Ugh, need to be converted to media entities
drush mim upgrade_d6_node_insert_box
drush mim upgrade_d6_node_person

# Needs all sorts of help:
# - Remapping from image nodes to image media entities
# - Convert DME tags
# - Set main image field
# - Populate address fields (from taxonomy, whoops)
# - Convert contributors from taxonomy to person nodes (?)
#drush mim upgrade_d6_node_story --feedback=100
