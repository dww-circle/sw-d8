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
    // This block must be cached separately for every page/route. Define this
    // render array here so that if we bail early, the render system knows to
    // only cache the empty response for the specific route that generated it.
    $block = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    $node = \Drupal::routeMatch()->getParameter('node');
    if (! $node instanceof \Drupal\node\NodeInterface) {
      return $block;
    }

    if ($node->bundle() != 'story') {
      return $block;
    }

    $topic = $node->get('field_topic')->getValue();
    if (empty($topic[0]['target_id']) || $topic[0]['target_id'] == SW_TOPIC_NONE_TID) {
      return $block;
    }

    // view_builder = \Drupal::entityManager()->getViewBuilder('node');
    return [
      '#title' => $this->t('Further reading'),
      '#markup' => 'Further reading for ' . $node->label(),
    ] + $block;
  }

}
