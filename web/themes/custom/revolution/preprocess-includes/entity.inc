<?php
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
