#!/bin/zsh

# Terms
rm -f sw-migrate.4.taxonomy-terms.out sw-migrate.4.taxonomy-terms.err
drush mim upgrade_d6_taxonomy_term > sw-migrate.4.taxonomy-terms.out 2> sw-migrate.4.taxonomy-terms.err
