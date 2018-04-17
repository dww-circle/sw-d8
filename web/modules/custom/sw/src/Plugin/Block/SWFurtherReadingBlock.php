<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\TermInterface;
use Drupal\node\NodeInterface;

/**
 * Provides the list of 'Further reading' articles on each story page.
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
   * Static cache of the block's render array body, keyed by node ID.
   *
   * This is shared across all instances of the class so we don't re-run the
   * same queries (which would happen anytime the cache is cleared, since we're
   * using the same block in two regions of the page, and the render array
   * doesn't save us in time).
   */
  static protected $blockBody = [];

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
  protected $mainTopic;

  /**
   * The parent term ID of the main topic we're searching, or 0 if a top-level topic.
   *
   * @var integer
   */
  protected $mainTopicParent;

  /**
   * The original secondary topic term ID of the article we're searching.
   *
   * @var integer
   */
  protected $secondaryTopic;

  /**
   * The parent term ID of the secondary topic we're searching, or 0 if a top-level topic.
   *
   * @var integer
   */
  protected $secondaryTopicParent;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->story = NULL;
    $this->maxRelated = 5; // @todo Should this be configurable?
    $this->relatedArticles = [];
    $this->searchedTopics = [SW_TOPIC_NONE_TID]; // The 'None' topic is always invalid.
    $this->mainTopic = 0;
    $this->mainTopicParent = NULL;
    $this->secondaryTopic = 0;
    $this->secondaryTopicParent = NULL;
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

    // Check our static cache to avoid re-building everything for the 2nd block on the page.
    if (!empty(SWFurtherReadingBlock::$blockBody[$node->id()])) {
      return SWFurtherReadingBlock::$blockBody[$node->id()];
    }

    $main_topic = $node->get('field_topic')->getValue();
    if (empty($main_topic[0]['target_id']) || $main_topic[0]['target_id'] == SW_TOPIC_NONE_TID) {
      return $block;
    }

    $this->story = $node;
    $this->mainTopic = $main_topic[0]['target_id'];

    $secondary_topic = $this->story->get('field_secondary_topic')->getValue();
    if (!empty($secondary_topic[0]['target_id']) && $secondary_topic[0]['target_id'] != SW_TOPIC_NONE_TID) {
      $this->secondaryTopic = $secondary_topic[0]['target_id'];
    }
    else {
      $this->secondaryTopic = 0;
    }

    // If we got this far, the block is going to be generated. Add a cache tag
    // for the specific node so the system will invalidate it whenever that
    // story is updated. Also add tags for the main and secondary topic terms.
    $block['#cache']['tags'][] = 'node:' . $this->story->id();
    $block['#cache']['tags'][] = 'taxonomy_term:' . $this->mainTopic;
    if (!empty($this->secondaryTopic)) {
      $block['#cache']['tags'][] = 'taxonomy_term:' . $this->secondaryTopic;
    }

    $this->findRelatedArticles();
    if (!empty($this->relatedArticles)) {
      $block['articles'] = $this->swGetStoryListArray($this->relatedArticles);
    }

    // Now that we've done all that work, stash this render array in our static
    // cache so the 2nd block on the page (in the story footer) doesn't have to
    // re-do everything.
    SWFurtherReadingBlock::$blockBody[$node->id()] = $block;

    return $block;
  }

  /**
   * Initialize the info about parents of the original topic(s).
   */
  protected function initializeTopicParents() {
    if (!isset($this->mainTopicParent)) {
      $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
      $main_parents = $term_storage->loadParents($this->mainTopic);
      if (!empty($main_parents)) {
        $main_parent_tids = array_keys($main_parents);
        $this->mainTopicParent = reset($main_parent_tids);
      }
      else {
        $this->mainTopicParent = 0;
      }
      if (!empty($this->secondaryTopic)) {
        $secondary_parents = $term_storage->loadParents($this->secondaryTopic);
        if (!empty($secondary_parents)) {
          $secondary_parent_tids = array_keys($secondary_parents);
          $this->secondaryTopicParent = reset($secondary_parent_tids);
        }
        else {
          $this->secondaryTopicParent = 0;
        }
      }
    }
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
      $this->$method();
      if (count($this->relatedArticles) >= $this->maxRelated) {
        break;
      }
    }
  }

  /**
   * Related articles search - phase 1: Exact match of the original topics.
   */
  protected function findRelatedArticlesPhase1() {
    $tids[] = $this->mainTopic;
    if (!empty($this->secondaryTopic)) {
      $tids[] = $this->secondaryTopic;
    }
    $this->searchTopics($tids, $this->maxRelated);
  }

  /**
   * Related articles search - phase 2: All descendants of both original topics.
   */
  protected function findRelatedArticlesPhase2() {
    $num_todo = $this->maxRelated - count($this->relatedArticles);
    $tids = [];
    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');

    // First, find the parents (if any) of both topics. If we're searching a
    // top-level topic, the behavior of this phase is different. And if we
    // survive until phase 3, we'll need these TIDs, anyway.
    $this->initializeTopicParents();

    // Now, find all the children of both terms.
    $main_child_tids = [];
    $secondary_child_tids = [];
    $main_children = $term_storage->loadTree('topic', $this->mainTopic);
    if (!empty($main_children)) {
      foreach ($main_children as $child_term) {
        $main_child_tids[] = $child_term->tid;
      }
    }
    if (!empty($this->secondaryTopic)) {
      $secondary_children = $term_storage->loadTree('topic', $this->secondaryTopic);
      if (!empty($secondary_children)) {
        foreach ($secondary_children as $child_term) {
          $secondary_child_tids[] = $child_term->tid;
        }
      }
    }

    // If neither topic has any children, we have to bail now.
    if (empty($main_child_tids) && empty($secondary_child_tids)) {
      return;
    }

    // Set some booleans to help make the logic more obvious below.
    $main_is_top_level = empty($this->mainTopicParent);
    $secondary_is_top_level = !empty($this->secondaryTopic) && empty($this->secondaryTopicParent);

    // If either one is a top-level topic, we have to be careful.
    if ($main_is_top_level || $secondary_is_top_level) {
      // If *both* are top-level, or we only have a main topic, search children
      // of main (then children of secondary, if any).
      if ($main_is_top_level && ($secondary_is_top_level || empty($this->secondaryTopic))) {
        $all_child_tids = array_merge($main_child_tids, $secondary_child_tids);
        $this->searchEachTopic($all_child_tids);
        return;
      }

      // Otherwise, prefer finding related stories from the more-specific topic
      // (if possible).

      // If secondary is top and main is not (but has children), start there.
      if ($secondary_is_top_level && !$main_is_top_level) {
        if (!empty($main_child_tids)) {
          $this->searchTopics($main_child_tids, $num_todo);
          if (count($this->relatedArticles) >= $this->maxRelated) {
            return;
          }
        }
        // We'll probably never get here, but now we have to do depth-first
        // search of secondary topic children, 1-by-1.
        if (!empty($secondary_child_tids)) {
          $this->searchEachTopic($secondary_child_tids);
          return;
        }
      }
      // If main is top-level and secondary (which, if we're still here, we
      // already know is not top-level) has children, start there.
      elseif ($main_is_top_level && !$secondary_is_top_level) {
        if (!empty($secondary_child_tids)) {
          $this->searchTopics($secondary_child_tids, $num_todo);
          if (count($this->relatedArticles) >= $this->maxRelated) {
            return;
          }
        }
        // We'll probably never get here, but now we have to do depth-first
        // search of main topic children, 1-by-1.
        if (!empty($main_child_tids)) {
          $this->searchEachTopic($main_child_tids);
          return;
        }
      }
    }

    // Otherwise, neither topic is top-level (which is the most common case).
    // Search all child terms from both topics in a single query.
    else {
      $tids = array_merge($main_child_tids, $secondary_child_tids);
      $this->searchTopics($tids, $num_todo);
    }

  }

  /**
   * Related articles search - phase 3: Immediate parents.
   */
  protected function findRelatedArticlesPhase3() {
    // This should have already happened, but just in case...
    $this->initializeTopicParents();
    $tids = [];
    foreach (['mainTopicParent', 'secondaryTopicParent'] as $parent_key) {
      if (!empty($this->$parent_key)) {
        $tids[] = $this->$parent_key;
      }
    }
    if (!empty($tids)) {
      $num_todo = $this->maxRelated - count($this->relatedArticles);
      $this->searchTopics($tids, $num_todo);
    }
  }

  /**
   * Related articles search - phase 4: Siblings
   */
  protected function findRelatedArticlesPhase4() {
    $num_todo = $this->maxRelated - count($this->relatedArticles);
    $tids = [];

    // @todo

    if (!empty($tids)) {
      $this->searchEachTopic($tids);
    }
  }

  /**
   * Helper function to search a set of topics 1-by-1.
   *
   * @param array $tids
   *   Array of topic term IDs to search.
   *
   * @return boolean
   *   TRUE if we found all we need, FALSE if we still haven't hit the max.
   */
  protected function searchEachTopic(array $tids) {
    foreach ($tids as $tid) {
      $todo = $this->maxRelated - count($this->relatedArticles);
      $this->searchTopics([$tid], $todo);
      if (count($this->relatedArticles) >= $this->maxRelated) {
        return TRUE;
      }
    }
    return FALSE;
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
      $this->relatedArticles = array_merge($this->relatedArticles, $nids);
    }
    $this->searchedTopics = array_merge($this->searchedTopics, $valid_tids);
    return $nids;
  }

}
