#!/bin/zsh

# Uploads (e.g. files attached to nodes)
rm -f d6_upload.out;
/usr/bin/time drush mim upgrade_d6_upload > d6_upload.out 2>&1;
