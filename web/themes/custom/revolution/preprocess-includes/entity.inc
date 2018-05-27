<?php

use Drupal\Core\Url;

/*
 * Implements hook_preprocess_HOOK().
 */
function revolution_preprocess_eck_entity(&$vars) {

  $entity = $vars['entity']['#entity'];
  $classes = &$vars['attributes']['class'];

  $bundletype = $entity->bundle(); // Such as basic accordion.
  $entitytype = $entity->getEntityTypeId(); // Such as slice.

  $classes[] = 'entity-' . str_replace('_', '-', $entitytype);
  $classes[] = str_replace('_', '-', $bundletype);
  $classes[] = 'clearfix';

}

/**
 * Implements hook_preprocess_HOOK() for media.
 */
function revolution_preprocess_media(&$variables) {
  // Invoke the bundle-specific callback (if any).
  $function = __FUNCTION__ . '__' . $variables['media']->bundle();
  if (function_exists($function)) {
    $function($variables);
  }
}

/**
 * Preprocess function for image media.
 *
 * Sets the 'image_url' variable for our twig template to make the image a link.
 */
function revolution_preprocess_media__image(&$variables) {
  $image_url = $variables['media']->get('field_image_url')->getValue();
  if (!empty($image_url[0]['uri'])) {
    $variables['image_url'] = Url::fromUri($image_url[0]['uri'])->toString();
  }
}

function revolution_preprocess_media__book(&$variables) {
  $image_url = $variables['media']->get('field_image_url')->getValue();
  if (!empty($image_url[0]['uri'])) {
    $variables['image_url'] = $image_url[0]['uri'];
  }
}
