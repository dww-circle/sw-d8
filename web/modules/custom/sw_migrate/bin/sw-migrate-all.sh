#!/bin/zsh

for phase ('1.users' '2.files' '3.taxonomy-terms' '4.image-media' '5.nodes' '6.url_alias' '7.redirect' '7.node-upload' '8.entityqueue' '9.paragraphs' '10.shortcuts') {
  rm -f $phase.out;
  script="./sw-migrate-content.$phase.sh";
  echo "Running $script";
  /usr/bin/time "$script" > "$phase.out" 2>&1;
}
