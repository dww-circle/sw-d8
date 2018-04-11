<?php
/*
 * Implements hook_theme_suggestions_page_alter().
 */

function revolution_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $node     = \Drupal::routeMatch()->getParameter('node');
  $revision = \Drupal::routeMatch()->getRawParameter('node_revision');
  $view_id  = \Drupal::routeMatch()->getParameter('view_id');

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
  elseif (!empty($view_id)) {
    switch ($view_id) {
      case 'sw_topic_stories':
        $suggestions[] = 'page__topic_stories';
        break;

      case 'sw_series_stories':
        $suggestions[] = 'page__series_stories';
        break;

    }
  }
}
