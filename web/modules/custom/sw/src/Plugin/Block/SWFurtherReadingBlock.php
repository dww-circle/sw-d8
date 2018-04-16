<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\TermInterface;
use Drupal\node\NodeInterface;

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

  use SWTeaserBlockTrait;

  /**
   * The story we're finding related articles for.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $story;

  /**
   * Maximum number of related articles to find.
   *
   * @var integer
   */
  protected $maxRelated;

  /**
   * Array of node IDs (nids) of related articles for a given story.
   *
   * @var array
   */
  protected $relatedArticles;

  /**
   * Array of term IDs (tids) of topics we've already searched.
   *
   * @var array
   */
  protected $searchedTopics;

  /**
   * The original main topic term ID of the article we're searching.
   *
   * @var integer
   */
  protected $originalMainTopic;

  /**
   * The original secondary topic term ID of the article we're searching.
   *
   * @var integer
   */
  protected $originalSecondaryTopic;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->story = NULL;
    $this->maxRelated = 5; // @todo Should this be configurable?
    $this->relatedArticles = [];
    $this->searchedTopics = [SW_TOPIC_NONE_TID]; // The 'None' topic is always invalid.
    $this->originalMainTopic = 0;
    $this->originalSecondaryTopic = 0;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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

    $main_topic = $node->get('field_topic')->getValue();
    if (empty($main_topic[0]['target_id']) || $main_topic[0]['target_id'] == SW_TOPIC_NONE_TID) {
      return $block;
    }

    $this->story = $node;
    $this->originalMainTopic = $main_topic[0]['target_id'];

    $secondary_topic = $this->story->get('field_secondary_topic')->getValue();
    if (!empty($secondary_topic[0]['target_id']) && secondary_topic[0]['target_id'] != SW_TOPIC_NONE_TID) {
      $this->originalSecondaryTopic = $secondary_topic[0]['target_id'];
    }
    else {
      $this->originalSecondaryTopic = 0;
    }

    // If we got this far, the block is going to be generated. Add a cache tag
    // for the specific node so the system will invalidate it whenever that
    // story is updated. Also add tags for the main and secondary topic terms.
    $block['#cache']['tags'][] = 'node:' . $this->story->id();
    $block['#cache']['tags'][] = 'taxonomy_term:' . $this->originalMainTopic;
    if (!empty($this->originalSecondaryTopic)) {
      $block['#cache']['tags'][] = 'taxonomy_term:' . $this->originalSecondaryTopic;
    }

    $this->findRelatedArticles();
    if (!empty($this->relatedArticles)) {
      $block['articles'] = $this->swGetStoryListArray($this->relatedArticles);
    }

    return $block;
  }

  /**
   * Invoke all the search phases, in order, until we find enough related articles.
   *
   * Each phase-specific method returns FALSE if there's more to find, TRUE if
   * we hit the limit.
   */
  protected function findRelatedArticles() {
    for ($i = 1; $i<=4; $i++) {
      $method = "findRelatedArticlesPhase$i";
      if ($this->$method()) {
        break;
      }
    }
  }

  /**
   * Related articles search - phase 1: Exact match of the original topics.
   */
  protected function findRelatedArticlesPhase1() {
    $tids[] = $this->originalMainTopic;
    if (!empty($this->originalSecondaryTopic)) {
      $tids[] = $this->originalSecondaryTopic;
    }
    $this->searchTopics($tids, $this->maxRelated);
    return count($this->relatedArticles) >= $this->maxRelated;
  }

  /**
   * Related articles search - phase 2: All descendents of both original topics.
   */
  protected function findRelatedArticlesPhase2() {
    $num_todo = $this->maxRelated - count($this->relatedArticles);
    $tids = [];
    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
    $main_children = $term_storage->loadTree('topic', $this->originalMainTopic);
    if (!empty($main_children)) {
      foreach ($main_children as $child_term) {
        $tids[] = $child_term->tid;
      }
    }
    if (!empty($this->originalSecondaryTopic)) {
      $secondary_children = $term_storage->loadTree('topic', $this->originalSecondaryTopic);
      if (!empty($secondary_children)) {
        foreach ($secondary_children as $child_term) {
          $tids[] = $child_term->tid;
        }
      }
    }
    if (!empty($tids)) {
      $this->searchTopics($tids, $num_todo);
    }
    return count($this->relatedArticles) >= $this->maxRelated;
  }

  /**
   * Related articles search - phase 3: Immediate parents.
   */
  protected function findRelatedArticlesPhase3() {
    $num_todo = $this->maxRelated - count($this->relatedArticles);
    $tids = [];
    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
    $main_parents = $term_storage->loadParents($this->originalMainTopic);
    if (!empty($main_parents)) {
      $tids += array_keys($main_parents);
    }
    if (!empty($this->originalSecondaryTopic)) {
      $secondary_parents = $term_storage->loadParents($this->originalSecondaryTopic);
      if (!empty($secondary_parents)) {
        $tids += array_keys($secondary_parents);
      }
    }
    if (!empty($tids)) {
      $this->searchTopics($tids, $num_todo);
    }
    return count($this->relatedArticles) >= $this->maxRelated;
  }

  /**
   * Related articles search - phase 4: Siblings
   */
  protected function findRelatedArticlesPhase4() {
    $num_todo = $this->maxRelated - count($this->relatedArticles);
    $tids = [];

    // @todo

    if (!empty($tids)) {
      $this->searchTopics($tids, $num_todo);
    }
    return count($this->relatedArticles) >= $this->maxRelated;
  }

  /**
   * Build and execute a query to find articles in a given array of topics.
   *
   * The articles are sorted in reverse chronological order, and filtered by
   * story weight. We avoid searching the same term multiple times, and we
   * always exclude the current story and any related articles we've already
   * found. Found stories are saved in $this->relatedArticles.
   *
   * @param array $topics
   *   Numeric term ID (TID) values for the topics to search.
   * @param integer $length
   *   The number of stories to search for. Defaults to 5.
   *
   * @return array
   *   Array of node IDs for valid stories that match the given topics.
   */
  protected function searchTopics(array $topics, $length = 5) {
    // Ignore TIDs we've already searched.
    $valid_tids = array_diff($topics, $this->searchedTopics);
    if (empty($valid_tids)) {
      return [];
    }
    $query = \Drupal::database()->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid']);
    $query->join('taxonomy_index', 'ti', 'nfd.nid = ti.nid');
    $query->join('node__field_story_weight', 'nfsw', 'nfd.nid = nfsw.entity_id AND nfd.vid = nfsw.revision_id');
    $query->condition('ti.tid', $valid_tids, 'IN');
    // Limit ourselves to stories more "important" (lighter) than or equal to 0.
    // @todo: Make this configurable?
    $query->condition('nfsw.field_story_weight_value', 0, '<=');
    // Don't let the current story appear as related to itself.
    $query->condition('nfd.nid', $this->story->id(), '!=');
    // Prevent duplicates (e.g. from main vs. secondary topics pointing to the same stories in different phases).
    if (!empty($this->relatedArticles)) {
      $query->condition('nfd.nid', $this->relatedArticles, 'NOT IN');
    }
    $query->orderBy('nfd.created', 'DESC');
    $query->range(0, $length);
    $nids = $query->execute()->fetchCol();
    if (!empty($nids)) {
      $this->relatedArticles += $nids;
    }
    $this->searchedTopics += $valid_tids;
    return $nids;
  }

}
