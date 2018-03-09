#!/bin/zsh

# Terms
rm -f 4.mim.taxonomy-terms.out;
/usr/bin/time drush mim upgrade_d6_taxonomy_term > 4.mim.taxonomy-terms.out 2>&1
