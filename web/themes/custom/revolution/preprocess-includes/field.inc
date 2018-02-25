<?php
/*
 * Implements hook_preprocess_HOOK().
 */

function revolution_preprocess_field(&$variables) {

  $entity = $variables['entity_type'];
  $field_name = $variables['field_name'];

  // Allow preprocess callbacks.
  $functions[] = __FUNCTION__ . '__' . $entity;
  $functions[] = __FUNCTION__ . '__' . $field_name;
  $functions[] = __FUNCTION__ . '__' . $entity . '__' . $field_name;

  foreach ($functions as $function) {
    if (function_exists($function)) {
      $function($variables);
    }
  }
}
