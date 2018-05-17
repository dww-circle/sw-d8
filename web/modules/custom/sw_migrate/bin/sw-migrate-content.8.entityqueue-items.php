<?php

$args = drush_get_arguments();
$file_name = !empty($args[2]) ? $args[2] : 'sw3-entityqueue-items';
if (!empty($args[3])) {
  $queue_name = $args[3];
}

$csv_file = drupal_get_path('module', 'sw_migrate') . "/csv/$file_name.csv";
$csv_input = file($csv_file, FILE_IGNORE_NEW_LINES);

$fields = [
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'langcode',
  'delta',
  'items_target_id',
];

$insert = \Drupal::database()->insert('entity_subqueue__items')->fields($fields);
foreach ($csv_input as $row) {
  if (substr($row, 0, 1) === '#') continue;
  list($queue, $delta, $target_id) = explode(',', $row);
  $queue_id = !empty($queue_name) ? $queue_name : $queue;
  $insert->values([$queue_id, 0, $queue, $queue, 'en', $delta, $target_id]);
}
$insert->execute();
