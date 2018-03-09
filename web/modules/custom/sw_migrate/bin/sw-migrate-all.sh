#!/bin/zsh

for phase ('1.users' '2.files' '3.taxonomy-terms' '4.image-media' '5.nodes' '6.term-media' '6.term-node' '7.url_alias' '7.node-upload') {
  rm -f $phase.out;
  script="./sw-migrate-content.$phase.sh";
  echo "Running $script";
  /usr/bin/time "$script" > "$phase.out" 2>&1;
}
