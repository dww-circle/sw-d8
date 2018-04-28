#!/bin/zsh

rm -f 9.slice_pages.out
/usr/bin/time drush mim csv_slice_pages > 9.slice_pages.out 2>&1
