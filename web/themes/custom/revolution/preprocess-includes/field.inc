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
  // @todo This is dangerous and Wrong(tm). It'd be better to be getting a fresh
  // render array here with the right view mode in the first place.  Short of
  // that, if we're going to dynamically mess with #view_mode, we also have to
  // update the cache keys, or Drupal will cache a render array for the wrong
  // view mode (teaser-only where we expect an image, or image where we expect
  // teaser-only).
  if ($variables['element']['#object']->get('parent_field_name')->value == 'field_nested_left') {
    $variables['items'][0]['content']['#view_mode'] = 'teaser_image_medium';
    // @todo We should probably be more careful, but we know that the 4th item
    // (index 3) in the keys array is the view mode, so we can save the expense
    // of finding 'teaser' and replacing it by directly targetting the right item.
    $variables['items'][0]['content']['#cache']['keys'][3] = 'teaser_image_medium';
    if (!isset($variables['items'][1])) {
      $variables['items'][0]['attributes']->setAttribute('class', 'border-none');
    }
  }
}

/**
 * Implements hook_preprocess_FIELD__ENTITY__TYPE__FIELD().
 */
function revolution_preprocess_field__node__blog_critical_reading__field_article_url(&$variables) {

  // Use article_headline field for link title.
  $headline = $variables['element']['#object']->get('field_article_headline')->getValue();
  $variables['items'][0]['content']['#title'] = $headline[0]['value'];
}
