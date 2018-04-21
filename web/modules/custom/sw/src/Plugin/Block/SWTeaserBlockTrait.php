<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\node\Entity\Node;

/**
 * Defines common methods for SW blocks that deal with (story) teasers.
 */
trait SWTeaserBlockTrait {

  /**
   * Generate a render array for an item_list of stories, displayed as teasers.
   *
   * @param array $nids
   *   Array of node IDs to load and render.
   *
   * @return array
   *   A render array for the given stories.
   */
  protected function swGetStoryListArray(array $nids) {
    $stories = Node::loadMultiple($nids);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $items = [];
    foreach ($stories as $story) {
      $items[] = $view_builder->view($story, 'teaser');
    }
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ];
  }

}
