#!/bin/zsh

# Actual nodes

#for bundle (d6_node_page d6_node_blog_couldnt_make_it_up d6_node_blog_critical_reading d6_node_insert_box d6_node_person d6_node_story ) {
#for bundle (d6_node_page d6_node_blog_couldnt_make_it_up) {
for bundle (d6_node_blog_critical_reading d6_node_insert_box d6_node_person d6_node_story ) {
  echo "Working on $bundle";
  rm -f $bundle.out $bundle.err
  time drush mim upgrade_$bundle > $bundle.out 2> $bundle.err
}

#drush mim upgrade_d6_node_page
#drush mim upgrade_d6_node_blog_couldnt_make_it_up
#drush mim upgrade_d6_node_blog_critical_reading
#drush mim upgrade_d6_node_insert_box
#drush mim upgrade_d6_node_person

#drush mim upgrade_d6_node_image    # Ugh, need to be converted to media entities

# Needs all sorts of help:
# - Remapping from image nodes to image media entities
# - Convert DME tags
# - Set main image field
# - rewrite hyphens to <hr>
# ?
#drush mim upgrade_d6_node_story
