#!/bin/sh

# Actual node revisions.
drush mim upgrade_d6_node_revision_page
drush mim upgrade_d6_node_revision_blog_couldnt_make_it_up
drush mim upgrade_d6_node_revision_blog_critical_reading --feedback=100
# drush mim upgrade_d6_node_revision_image    // Ugh, need to be converted to media entities
drush mim upgrade_d6_node_revision_insert_box --feedback=100
drush mim upgrade_d6_node_revision_person --feedback=100
drush mim upgrade_d6_node_revision_story --feedback=100
