#!/bin/zsh

# Map D6 terms to nodes.
echo "Starting node term migration.";
# vids 1, 2 and 10 are handled directly during d6_node_story.
# vids 3 and 11 are gone, so we have to enumerate the rest.
for i (4 5 6 7 8 9 12 13) {
  echo "Working on vocabulary ID $i";
  file="term_node_vid_$i";
  rm -f $file.out
  /usr/bin/time drush mim "upgrade_d6_term_node_$i" > $file.out 2>&1;
}
