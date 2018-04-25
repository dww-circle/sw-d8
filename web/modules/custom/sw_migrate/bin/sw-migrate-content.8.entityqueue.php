<?php

$csv_file = drupal_get_path('module', 'sw_migrate') . '/csv/sw3-entityqueue.csv';
$csv_input = file($csv_file, FILE_IGNORE_NEW_LINES);

// Clean out everything already in the table.
\Drupal::database()->delete('entity_subqueue__items')->execute();

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
  list($queue, $position, $target_id) = explode(',', $row);
  // D6 uses 'position' counting from 1, D8 uses $delta counting from 0;
  $delta = $position - 1;
  $insert->values([$queue, 0, $queue, $queue, 'en', $delta, $target_id]);
}
$insert->execute();
