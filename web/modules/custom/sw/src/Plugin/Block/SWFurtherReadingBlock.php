<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
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
   * The current time that a request for this block is made.
   *
   * @var integer
   */
  protected $requestTime;

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
    $this->requestTime = \Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME');
    $this->relatedArticles = [];
    $this->searchedTopics = [SW_TOPIC_NONE_TID]; // The 'None' topic is always invalid.
    $this->mainTopic = NULL;
    $this->mainTopicParent = NULL;
    $this->secondaryTopic = NULL;
    $this->secondaryTopicParent = NULL;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'story_query_weight_limit' => 0,
      'story_query_date_limit' => 0,
      'related_articles_date_limit' => 90,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['story_query_weight_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Story weight limit'),
      '#description' => $this->t('Stories must be lighter than this weight to be shown.'),
      '#default_value' => isset($config['story_query_weight_limit']) ? $config['story_query_weight_limit'] : 0,
      '#min' => -10,
      '#max' => 10,
    ];
    $form['story_query_date_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Date limit'),
      '#description' => $this->t('Number of days in the past to draw articles from, or 0 for no date limit.'),
      '#default_value' => isset($config['story_query_date_limit']) ? $config['story_query_date_limit'] : 0,
      '#min' => 0,
    ];
    $form['related_articles_date_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Related articles field shelf-life'),
      '#description' => $this->t('Stories older than than this many days will ignore their own custom related articles field.'),
      '#default_value' => isset($config['related_articles_date_limit']) ? $config['related_articles_date_limit'] : 0,
      '#min' => 0,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $config_keys = [
      'story_query_weight_limit',
      'story_query_date_limit',
      'related_articles_date_limit',
    ];
    foreach ($config_keys as $config_key) {
      $this->configuration[$config_key] = $values[$config_key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    return $this->buildFromNode($node);
  }

  /**
   * Build the block for a specific story.
   *
   * @param \Drupal\node\Entity\NodeInterface $node
   *   The node entity to build a further reading block for.
   *
   * @return array
   *   The render array for the Further reading block of a given story.
   */
  public function buildFromNode(NodeInterface $node) {
    // This block must be cached separately for every page/route. Define this
    // render array here so that if we bail early, the render system knows to
    // only cache the empty response for the specific route that generated it.
    $block = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    if (empty($node)) {
      return $block;
    }
    if (! $node instanceof \Drupal\node\NodeInterface) {
      return $block;
    }

    if ($node->bundle() != 'story') {
      return $block;
    }

    $config = $this->getConfiguration();

    // Check our static cache to avoid re-building everything for the 2nd block on the page.
    $cache_ids = [
      $node->id(),
      $config['story_query_weight_limit'],
      $config['story_query_date_limit'],
      $config['related_articles_date_limit'],
    ];
    $cache_id = implode(':', $cache_ids);
    if (!empty(SWFurtherReadingBlock::$blockBody[$cache_id])) {
      return SWFurtherReadingBlock::$blockBody[$cache_id];
    }

    $this->story = $node;
    $this->initializeTopics();
    // If the story defines custom related articles, put those at the top of our list.
    $this->initializeRelatedArtices();

    // We've got a story and possibly topics to search from. Add a cache tag for
    // the specific node so the system will invalidate the block whenever that
    // story is updated. Also add tags for the main and secondary topic terms.
    $block['#cache']['tags'][] = 'node:' . $this->story->id();
    foreach (['mainTopic', 'secondaryTopic'] as $key) {
      if (!empty($this->$key)) {
        $block['#cache']['tags'][] = 'taxonomy_term:' . $this->$key;
      }
    }

    // If we don't have a main topic, nor any custom related articles,
    // we can't render anything and have to bail now.
    if (empty($this->mainTopic) && empty($this->relatedArticles)) {
      SWFurtherReadingBlock::$blockBody[$node->id()] = $block;
      return $block;
    }

    // If the story didn't already define enough custom relateds, and we have
    // any topics set, search based on the topics.
    if (count($this->relatedArticles) <= $this->maxRelated && !empty($this->mainTopic)) {
      $this->findRelatedArticles();
    }

    // If we have anything at all, render it as a story list of teasers.
    if (!empty($this->relatedArticles)) {
      $block['articles'] = $this->swGetStoryListArray($this->relatedArticles);
      // Also, add cache tags for every article in the list.
      foreach ($this->relatedArticles as $nid) {
        $block['#cache']['tags'][] = 'node:' . $nid;
      }
    }

    // Now that we've done all that work, stash this render array in our static
    // cache so the 2nd block on the page (in the story footer) doesn't have to
    // re-do everything.
    SWFurtherReadingBlock::$blockBody[$cache_id] = $block;

    return $block;
  }

  /**
   * Initialize the info about the original topic(s).
   */
  protected function initializeTopics() {
    if (!isset($this->mainTopic)) {
      $fields = [
        'field_topic' => 'mainTopic',
        'field_secondary_topic' => 'secondaryTopic',
      ];
      foreach ($fields as $field_name => $memberName) {
        $topic = $this->story->get($field_name)->getValue();
        if (!empty($topic[0]['target_id']) && $topic[0]['target_id'] != SW_TOPIC_NONE_TID) {
          $this->$memberName = $topic[0]['target_id'];
        }
        else {
          $this->$memberName = 0;
        }
      }
    }
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
   * Harvest custom related articles from the story.
   */
  protected function initializeRelatedArtices() {
    $related_articles = $this->story->get('field_related_articles')->getValue();
    $nids = [];
    if (!empty($related_articles)) {
      foreach ($related_articles as $related) {
        if (!empty($related['target_id'])) {
          $nids[] = $related['target_id'];
        }
      }
    }
    if (!empty($nids)) {
      $config = $this->getConfiguration();
      if (!empty($config['related_articles_date_limit'])) {
        $time_limit = $this->requestTime - ($config['related_articles_date_limit'] * 86400); // (60 * 60 * 24 = seconds/day)
        // Story is too old for this field to matter, bail now.
        if ($this->story->get('created')->value < $time_limit) {
          return;
        }
      }
      // Only keep the published articles.
      $query = \Drupal::database()->select('node_field_data', 'nfd')
        ->fields('nfd', ['nid'])
        ->condition('nfd.status', 1, '=')
        ->condition('nfd.nid', $nids, 'IN');
      $published_nids = $query->execute()->fetchCol();
      $this->relatedArticles = array_intersect($nids, $published_nids);
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
    $this->searchTopics($tids);
  }

  /**
   * Related articles search - phase 2: All descendants of both original topics.
   */
  protected function findRelatedArticlesPhase2() {
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
          $this->searchTopics($main_child_tids);
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
          $this->searchTopics($secondary_child_tids);
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
      $this->searchTopics($tids);
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
      $this->searchTopics($tids);
    }
  }

  /**
   * Related articles search - phase 4: Siblings
   */
  protected function findRelatedArticlesPhase4() {
    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
    foreach (['mainTopicParent', 'secondaryTopicParent'] as $parent_key) {
      $tids = [];
      if (!empty($this->$parent_key)) {
        $siblings = $term_storage->loadTree('topic', $this->$parent_key);
        if (!empty($siblings)) {
          foreach ($siblings as $term) {
            // We don't have to care about hitting the original topics (or their
            // children), since we'll avoid re-querying any of them thanks to
            // $this->searchedTopics being enforced inside searchTopics().
            $tids[] = $term->tid;
          }
          if ($this->searchEachTopic($tids)) {
            // searchEachTopic returns TRUE if we're done. In that case, bail
            // before looking for secondary topic siblings.
            return;
          }
        }
      }
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
      $this->searchTopics([$tid]);
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
   *
   * @return array
   *   Array of node IDs for valid stories that match the given topics.
   */
  protected function searchTopics(array $topics) {
    // Ignore TIDs we've already searched.
    $valid_tids = array_diff($topics, $this->searchedTopics);
    if (empty($valid_tids)) {
      return [];
    }
    $num_needed = $this->maxRelated - count($this->relatedArticles);
    $config = $this->getConfiguration();

    // Don't let the current story appear as related to itself.
    $exclude_nids[] = $this->story->id();
    // Prevent duplicates (e.g. from main vs. secondary topics pointing to the
    // same stories in different phases).
    if (!empty($this->relatedArticles)) {
      $exclude_nids = array_merge($exclude_nids, $this->relatedArticles);
    }

    $query = sw_story_taxonomy_query($valid_tids, $exclude_nids, $num_needed, $config['story_query_weight_limit']);

    // Enforce the date limit (if any).
    if (!empty($config['story_query_date_limit'])) {
      $request_time = \Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME');
      $time_limit = $this->requestTime - ($config['story_query_date_limit'] * 86400); // (60 * 60 * 24 = seconds/day)
      $query->condition('nfd.created', $time_limit, '>=');
    }

    $nids = $query->execute()->fetchCol();
    if (!empty($nids)) {
      $this->relatedArticles = array_merge($this->relatedArticles, $nids);
    }
    $this->searchedTopics = array_merge($this->searchedTopics, $valid_tids);
    return $nids;
  }

}
