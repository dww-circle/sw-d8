<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\sw\Plugin\Block\SWRecentArticlesBase;

/**
 * A block for use on the front page for 'Recent stories'.
 *
 * It's turning out to be a pain in the ass to render this via a paragraph
 * slice. For now, it's another custom block. Either we'll just place it as a
 * block and be done, or we'll have to keep jumping through hoops to get it to
 * appear inside a paragraph slice for arbitrary placement on slice pages.
 *
 * @see http://tasks.socialistworker.org/node/1035
 *
 * @Block(
 *   id = "sw_recent_stories_block",
 *   admin_label = @Translation("SW Recent stories"),
 *   category = @Translation("SW"),
 * )
 */
class SWRecentStoriesBlock extends SWRecentArticlesBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'tags' => ['node_list'],
      ],
    ];
    $articles_by_date = $this->findRecentArticles();
    foreach ($articles_by_date as $pub_date => $articles) {
      $article_render_arrays = [];
      foreach ($articles as $article) {
        $article_render_arrays[] = $this->buildArticleRenderArray($article);
      }
      $build[$pub_date] = [
        'story_group' => [
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('story-group'),
           ),
          'header' => [
            '#prefix' => '<h3>',
            '#markup' => $this->getHeaderLabel($pub_date, 'F j, Y'),
            '#suffix' => '</h3>',
          ],
          'articles' => [
            '#theme' => 'item_list',
            '#list_type' => 'ul',
            '#items' => $article_render_arrays,
          ],
        ],
      ];
    }
    return $build;
  }

}
