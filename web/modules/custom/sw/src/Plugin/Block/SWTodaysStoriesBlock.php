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

  use SWTeaserBlockTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $all_articles = $this->findRecentArticles();
    // For this block, we only care about the first (most recent) day of stories.
    $latest_articles = array_shift($all_articles);
    return $this->swGetStoryListArray(array_keys($latest_articles));
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $recent_dates = $this->getRecentPublicationDates();
    $latest = array_shift($recent_dates);
    $current = date('Ymd');
    return ($latest == $current) ? $this->t("Today's Stories") : $this->t("Latest Stories");
  }
}
