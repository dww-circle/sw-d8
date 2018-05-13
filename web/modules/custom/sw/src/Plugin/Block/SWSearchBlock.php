<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * A site-wide block for a trivial search form.
 *
 * @Block(
 *   id = "sw_search_block",
 *   admin_label = @Translation("SW search block"),
 *   category = @Translation("SW"),
 * )
 */
class SWSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\sw\Form\SearchBlockForm');
  }
}
