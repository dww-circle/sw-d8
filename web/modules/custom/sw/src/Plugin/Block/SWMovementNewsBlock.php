<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * A block for the front page 'Movement News' listing.
 *
 * @Block(
 *   id = "sw_movement_news_block",
 *   admin_label = @Translation("SW Movement News"),
 *   category = @Translation("SW"),
 * )
 */
class SWMovementNewsBlock extends SWRecentArticlesBase {

  use SWTeaserBlockTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->swGetStoryListArray($this->getMovementNewsStories());
  }

  /**
   * Return an array of node IDs for all the stories that should appear in the block.
   *
   * Exclude all stories published "today".  Then, find the most recent article
   * from 'Labor' or 'Activist News' sections that is -2 or lighter as the lead
   * story. Then, grab the 5 most recent articles from those sections lighter
   * than weight 5 (minus today's stories and the lead article).
   *
   * @return array
   *   Node IDs (nid) for the stories that should appear in this block.
   */
  public function getMovementNewsStories() {
    // For this block, we want to exclude all the articles that were published "today".
    $all_articles = $this->findRecentArticles();
    $exclude_nids = array_keys(array_shift($all_articles));

    $tids = [SW_SECTION_LABOR_TID, SW_SECTION_ACTIVIST_NEWS_TID];
    $query = sw_story_taxonomy_query($tids, $exclude_nids, 1, -2);
    $lead_story_nids = $query->execute()->fetchCol();

    $exclude_nids = array_merge($exclude_nids, $lead_story_nids);
    // We need 5 more stories, and the weight_limit is also 5.
    $query = sw_story_taxonomy_query($tids, $exclude_nids, 5, 5);
    $other_nids = $query->execute()->fetchCol();

    return array_merge($lead_story_nids, $other_nids);
  }

}
