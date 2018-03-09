#!/bin/zsh

# Map D6 image node terms to D8 image media entities.

echo "Starting media term migration.";
for i (6 7 8 14 15 16 17 18) {
  echo "Working on vocabulary ID $i";
  file="term_media_vid_$i";
  rm -f $file.out
  /usr/bin/time drush mim "upgrade_d6_term_media_$i" > $file.out 2>&1;
}
