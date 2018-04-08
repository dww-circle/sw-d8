<?php
/*
 * Implements hook_theme_suggestions_page_alter().
 */

function revolution_theme_suggestions_page_alter(array &$suggestions, array $variables) {

  $current_route_name = \Drupal::service('current_route_match')->getRouteName();
  $node     = \Drupal::routeMatch()->getParameter('node');
  $revision = \Drupal::routeMatch()->getRawParameter('node_revision');

  // Allow page template per content type.
  if ($node) {
    if ($revision) {
      $revised_node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($revision);

      $content_type = $revised_node->bundle();
    }
    else {
      $content_type = $node->bundle();
    }

    $suggestions[] = 'page__node__' . $content_type;
  }

  // Set topic view pages to use own template..
  if ($current_route_name == 'view.sw_topic_stories.page_1') {
    $suggestions[] = 'page__' . 'topic';
  }
  // Set series view pages to use own template..
  if ($current_route_name == 'view.sw_series_stories.page_1') {
    $suggestions[] = 'page__' . 'series';
  }
}
