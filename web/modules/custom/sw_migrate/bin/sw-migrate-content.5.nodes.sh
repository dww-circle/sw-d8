#!/bin/zsh

# Actual nodes

# 'story' still needs all sorts of help:
# - Convert DME and move tags
# - Set main image field
# - rewrite hyphens to <hr>
# ?
for bundle (d6_node_page d6_node_person d6_node_insert_box d6_node_blog_couldnt_make_it_up d6_node_blog_critical_reading d6_node_story) {
  echo "Working on $bundle";
  rm -f $bundle.out
  /usr/bin/time drush mim upgrade_$bundle > $bundle.out 2>&1;
}
