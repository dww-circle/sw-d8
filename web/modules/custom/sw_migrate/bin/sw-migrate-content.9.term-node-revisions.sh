#!/bin/zsh

# Map terms to node revisions

# vids 3 and 11 are gone, so we have to enumerate these.
for i (1 2 4 5 6 7 8 9 10 12 13) {
  echo "Working on vocabulary ID $i";
  file="term_node_revision_vid_$i";
  rm -f $file.out $file.err;
  time drush mim "upgrade_d6_term_node_revision_$i" > $file.out 2> $file.err;
}
