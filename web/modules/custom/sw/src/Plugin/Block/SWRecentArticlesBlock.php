<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

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
    // @todo Potentially make the # of days configurable.
    $dates = $this->getRecentPublicationDates();
    $all_articles = $this->findRecentArticles($dates);

    $articles_by_date = [];
    foreach ($all_articles as $nid => $article) {
      $articles_by_date[$article->created_day][] = $this->buildArticleRenderArray($article);
    }

    $date_labels = [];
    $date_tabs = [];
    foreach ($articles_by_date as $pub_date => $articles) {
      $tab_id = 'recent-articles-tab-' . $pub_date;
      $label_id = 'recent-articles-date-' . $pub_date;
      $date_labels[$pub_date] = [
        '#markup' => '<a id="' . $label_id . '" href="#" class="recent-articles-date">' . $this->getTabLabel($pub_date) . '</a>',
      ];
      $date_tabs[$pub_date] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#wrapper_attributes' => [
          'class' => 'recent-articles-tab js-hide',
          'id' => $tab_id,
        ],
        '#items' => $articles,
      ];
    }

    return [
      'labels' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $date_labels,
        '#wrapper_attributes' => [
          'class' => 'recent-articles-dates',
        ],
      ],
      'dates' => [
        '#prefix' => '<div class="recent-articles-tabs">',
        'children' => $date_tabs,
        '#suffix' => '</div>',
      ],
      '#attached' => [
        'library' => [
          'sw/recent-articles',
        ],
      ],
    ];
  }

  /**
   * Helper function to find the 6 most recent days that SW published articles.
   *
   * Technically, this is all wrong, and not the Proper Drupal 8 Way(tm). It
   * assumes MySQL. It assumes entities are stored in the DB, not some fancy
   * plugable backend, etc. Thankfully, all these assumptions are true.
   *
   * @return array
   *   Array of publication dates, of the form YYYYMMDD.
   */
  protected function getRecentPublicationDates() {
    $query = \Drupal::database()->query("SELECT DATE_FORMAT((DATE_ADD('19700101', INTERVAL node_field_data.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') AS node_field_data_created_day FROM node_field_data node_field_data WHERE (node_field_data.status = '1') AND (node_field_data.type IN ('story')) GROUP BY node_field_data_created_day ORDER BY node_field_data_created_day DESC LIMIT 6;");
    return $query->fetchCol();
  }

  /**
   * Helper function to find all the articles published on the given dates.
   *
   * Technically, this is all wrong, and not the Proper Drupal 8 Way(tm). It
   * assumes MySQL. It assumes entities are stored in the DB, not some fancy
   * plugable backend, etc. Thankfully, all these assumptions are true.
   *
   * @param array $dates
   *   The publication dates to find articles for.
   *   Each value must be of the form 'YYYYMMDD'.
   *
   * @return array
   *   Array of article objects, indexed by nid, sorted by created_day (DESC)
   *   and story_weight (ASC).  Each object has the values nid, vid, title,
   *   story_weight, and created_day.
   */
  protected function findRecentArticles(array $dates) {
    $query = \Drupal::database()->query(
      "SELECT nfd.nid, nfd.vid, nfd.title, nfsw.field_story_weight_value AS story_weight, DATE_FORMAT((DATE_ADD('19700101', INTERVAL nfd.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') AS created_day FROM {node_field_data} nfd INNER JOIN {node__field_story_weight} nfsw ON nfd.nid = nfsw.entity_id WHERE (nfd.status = '1') AND (nfd.type = 'story') AND DATE_FORMAT((DATE_ADD('19700101', INTERVAL nfd.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') IN ( :days[] ) ORDER BY created_day DESC, story_weight ASC", [':days[]' => $dates]);
    return $query->fetchAllAssoc('nid');
  }

  /**
   * Build the appropriate render array for a given article.
   *
   * @param $article
   *   An article object as returned from our DB queries.
   *
   * @return array
   *   The render array to display the specific article.
   */
  protected function buildArticleRenderArray($article) {
    // @todo: If we denormalize the story_label into a separate field, we
    // don't need to incur the cost of all the full entity loads here.
    $node = \Drupal\node\Entity\Node::load($article->nid);
    foreach (['authors', 'interviewees'] as $field_id) {
      sw_load_referenced_entities($node, $field_id, ['Drupal\node\Entity\Node', 'loadMultiple']);
    }
    $node_url = new Url('entity.node.canonical', ['node' => $article->nid]);
    return [
      '#prefix' => '<a href="' . $node_url->toString() . '">',
      'story_label' => [
        '#markup' => sw_get_story_label($node, 'teaser'),
        '#prefix' => '<div class="story-label">',
        '#suffix' => '</div>',
      ],
      'headline' => [
        '#markup' => $article->title,
        '#prefix' => '<div class="headline">',
        '#suffix' => '</div>',
      ],
      '#suffix' => '</a>',
    ];
  }

  /** 
   * Build the appropriate tab label for a given publication date.
   *
   * @param integer $pub_date
   *   A publication date of the form YYYYMMDD.
   *
   * @return string
   *   The label to use for the tab: 'M/D'.
   */
  protected function getTabLabel($pub_date) {
    // Split the date into 2 character chunks.
    $parts = str_split((string)$pub_date, 2);
    // Treat the last 2 chunks as integers (to chop leading 0) and delimit with /.
    return (int)$parts[2] . '/' . (int)$parts[3];
  }

}
