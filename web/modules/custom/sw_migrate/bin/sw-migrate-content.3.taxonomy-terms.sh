#!/bin/zsh

# Terms
rm -f d6_taxonomy_term.out;
time drush mim upgrade_d6_taxonomy_term > d6_taxonomy_term.out 2>&1
