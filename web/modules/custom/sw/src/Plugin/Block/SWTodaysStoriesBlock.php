<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\node\Entity\Node;
use Drupal\sw\Plugin\Block\SWRecentArticlesBase;

/**
 * A block for "Latest Stories" in the article footer region.
 *
 * @Block(
 *   id = "sw_todays_stories_block",
 *   admin_label = @Translation("SW Today's Stories"),
 *   category = @Translation("SW"),
 * )
 */
class SWTodaysStoriesBlock extends SWRecentArticlesBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $all_articles = $this->findRecentArticles();
    // For this block, we only care about the first (most recent) day of stories.
    $latest_articles = array_shift($all_articles);
    // Load all the stories as full-blown entities
    $entities = \Drupal\node\Entity\Node::loadMultiple(array_keys($latest_articles));
    $render_controller = \Drupal::entityManager()->getViewBuilder('node');
    $items = [];
    foreach ($entities as $entity) {
      // Build the render arrays via building the 'teaser' view mode.
      $items[] = $render_controller->view($entity, 'teaser');
    }
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ];
  }

}
