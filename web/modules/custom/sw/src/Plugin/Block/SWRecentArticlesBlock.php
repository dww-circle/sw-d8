<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * A site-wide block for recent articles.
 *
 * @Block(
 *   id = "sw_recent_articles_block",
 *   admin_label = @Translation("SW Recent Articles"),
 *   category = @Translation("SW"),
 * )
 */
class SWRecentArticlesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => $this->t('Recent articles'),
      '#markup' => 'Recent: dww was here',
    ];
  }

}
