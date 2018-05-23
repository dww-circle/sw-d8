#!/bin/zsh

# Actual nodes
for bundle (d6_node_page d6_node_person d6_node_insert_box d6_node_blog_couldnt_make_it_up d6_node_blog_critical_reading d6_node_edition d6_node_story) {
  echo "Working on $bundle";
  rm -f $bundle.out
  /usr/bin/time drush mim upgrade_$bundle > $bundle.out 2>&1;
}
