<?php
/*
 * Implements hook_preprocess_HOOK().
 */

function revolution_preprocess_field(&$variables) {

  $entity = $variables['entity_type'];
  $field_name = $variables['field_name'];
  $type = $variables['element']['#bundle'];

  // Allow preprocess callbacks.
  $functions[] = __FUNCTION__ . '__' . $entity;
  $functions[] = __FUNCTION__ . '__' . $field_name;
  $functions[] = __FUNCTION__ . '__' . $entity . '__' . $field_name;
  $functions[] = __FUNCTION__ . '__' . $entity . '__' . $type . '__' .$field_name;

  foreach ($functions as $function) {
    if (function_exists($function)) {
      $function($variables);
    }
  }
}
/**
 * Implements hook_preprocess_FIELD__ENTITY__TYPE__FIELD().
 */
function revolution_preprocess_field__paragraph__nested__field_articles(&$variables) {

  // Show image on the first story on the left.
  if ($variables['element']['#object']->get('parent_field_name')->value == 'field_nested_left') {
    $variables['items'][0]['content']['#view_mode'] = 'teaser_image_medium';
  }
}
