#!/bin/zsh

# Files
rm -f d6_file.out d6_file.err
time drush mim upgrade_d6_file > d6_file.out 2> d6_file.err

# Uploads (e.g. files attached to nodes)
rm -f d6_upload.out d6_upload.err
time drush mim upgrade_d6_upload > d6_upload.out 2> d6_upload.err

