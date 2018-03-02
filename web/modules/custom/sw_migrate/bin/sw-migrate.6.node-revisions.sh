#!/bin/sh

# Actual node revisions.
drush mim upgrade_d6_node_revision:page
drush mim upgrade_d6_node_revision:blog_couldnt_make_it_up
drush mim upgrade_d6_node_revision:blog_critical_reading --feedback=100

# drush mim upgrade_d6_node_revision:edition  // Don't think we're migrating these (?)

# drush mim upgrade_d6_node_revision:image    // Ugh, need to be converted to media entities

drush mim upgrade_d6_node_revision:insert_box --feedback=100
drush mim upgrade_d6_node_revision:person --feedback=100
drush mim upgrade_d6_node_revision:story --feedback=100
