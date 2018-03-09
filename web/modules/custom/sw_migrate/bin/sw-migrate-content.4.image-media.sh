#!/bin/zsh

# Convert D6 image nodes into D8 image media entities

rm -f d6_node_image.out;
/usr/bin/time drush mim upgrade_d6_node_image > d6_node_image.out 2>&1;
