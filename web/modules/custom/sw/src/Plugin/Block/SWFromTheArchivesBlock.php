<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * A block for the story footer region for random 'From the archives' stories.
 *
 * @Block(
 *   id = "sw_from_the_archives_block",
 *   admin_label = @Translation("SW From the archives"),
 *   category = @Translation("SW"),
 * )
 */
class SWFromTheArchivesBlock extends BlockBase {

  use SWTeaserBlockTrait;

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

    // Load the entityqueue this is based on.
    $entityqueue = \Drupal::entityManager()->getStorage('entity_subqueue')->load('from_the_archives');
    if (empty($entityqueue)) {
      return $block;
    }

    // Get the full list of items in the queue.
    $queue_list = $entityqueue->get('items')->getValue();

    // Bail now if the queue is empty.
    if (empty($queue_list)) {
      return $block;
    }

    // Restrict ourselves to the top 10 stories in the queue.
    // @todo: Make this configurable?
    $active_stories = array_slice($queue_list, 0, 10);

    // Save the nid and delta of these "active" stories.
    // Preserving the deltas lets us easily sort later.
    foreach ($active_stories as $delta => $value) {
      $nids[] = $value['target_id'];
      $deltas[$value['target_id']] = $delta;
    }

    // Randomize the nids.
    shuffle($nids);

    // Harvest the top 5 stories from the random list to display in the block.
    // @todo: Make this configurable?
    $block_nids = array_slice($nids, 0, 5);

    // Sort the random stories by the original entityqueue ordering.
    foreach ($block_nids as $nid) {
      $ordered_list[$deltas[$nid]] = $nid;
    }
    ksort($ordered_list);

    // Load and render these stories as teasers.
    return $this->swGetStoryListArray($ordered_list) + $block;
  }
}
