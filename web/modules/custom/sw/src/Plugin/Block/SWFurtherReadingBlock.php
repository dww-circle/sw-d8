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
 *   id = "sw_further_reading_block",
 *   admin_label = @Translation("SW Further Reading"),
 *   category = @Translation("SW"),
 * )
 */
class SWFurtherReadingBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => $this->t('Further reading'),
      '#markup' => 'Further: dww was here',
    ];
  }

}
