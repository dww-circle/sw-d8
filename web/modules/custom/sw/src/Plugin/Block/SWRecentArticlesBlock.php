<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\sw\Plugin\Block\SWRecentArticlesBase;

/**
 * A site-wide block for recent articles.
 *
 * @Block(
 *   id = "sw_recent_articles_block",
 *   admin_label = @Translation("SW Recent Articles"),
 *   category = @Translation("SW"),
 * )
 */
class SWRecentArticlesBlock extends SWRecentArticlesBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $articles_by_date = $this->findRecentArticles();
    $date_labels = [];
    $date_tabs = [];
    foreach ($articles_by_date as $pub_date => $articles) {
      $tab_id = 'recent-articles-tab-' . $pub_date;
      $label_id = 'recent-articles-date-' . $pub_date;
      $header_id = 'recent-articles-header-' . $pub_date;
      $date_labels[$pub_date] = [
        '#markup' => '<a id="' . $label_id . '" href="#' . $header_id . '" class="recent-articles-date">' . $this->getTabLabel($pub_date) . '</a>',
      ];
      $article_render_arrays = [];
      foreach ($articles as $article) {
        $article_render_arrays[] = $this->buildArticleRenderArray($article);
      }
      $date_tabs[$pub_date] = [
        // Add headers for the non-JS case:
        // The tab links at least can send you to a named anchor.
        // The huge list of stories now makes sense since you see the dates.
        'header' => [
          '#prefix' => '<h3 id="' . $header_id . '" class="js-hide">',
          '#suffix' => '</h3>',
          '#markup' => $this->getHeaderLabel($pub_date),
        ],
        'articles' => [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#wrapper_attributes' => [
            'class' => 'recent-articles-tab js-hide',
            'id' => $tab_id,
          ],
          '#items' => $article_render_arrays,
        ],
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
      '#cache' => [
        'tags' => ['node_list'],
      ],
    ];
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

  /** 
   * Build the appropriate (non-JS) header label for a given publication date.
   *
   * @param integer $pub_date
   *   A publication date of the form YYYYMMDD.
   *
   * @return string
   *   The label to use for the h3 sub-header.
   */
  protected function getHeaderLabel($pub_date) {
    $datetime = \DateTime::createFromFormat('Ymd', $pub_date);
    return $datetime->format('l, F jS');
  }

}
