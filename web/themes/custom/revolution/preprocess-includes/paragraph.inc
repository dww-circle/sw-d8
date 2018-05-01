<?php
/*
 * Implements hook_preprocess_HOOK().
 */

use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__queue(&$variables) {
  // Get field values.
  $num_med_img = $variables['paragraph']->field_num->value;
  $results = $variables['paragraph']->field_results->value;
  $queue_id = $variables['elements']['field_subqueue'][0]['#plain_text'];

  if ($queue_id != NULL) {
    $entity_subqueue = \Drupal::entityManager()->getStorage('entity_subqueue')->load($queue_id);
    $queue_list = $entity_subqueue->get('items')->getValue();

    $i = 0;
    $nids = [];
    foreach ($queue_list as $item) {
      // Loop through until field_results value.
      if ($i == $results) {
        break;
      }
      $nids[] = $item['target_id'];
      $i++;
    }
    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);
    }
    if (!empty($nodes)) {
      $variables['groups'] = _revolution_story_groups_render($nodes, $num_med_img, TRUE);
    }
  }
}

/**
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__movement_news(&$variables) {
  $i = 0;
  $tids = [SW_SECTION_LABOR_TID, SW_SECTION_ACTIVIST_NEWS_TID];
  $query = sw_story_taxonomy_query($tids, [], 1, -2);
  $lead_story_nids = $query->execute()->fetchCol();
  // We need 5 more stories, and the weight_limit is also 5.
  $query = sw_story_taxonomy_query($tids, $lead_story_nids, 5, 5);
  $other_nids = $query->execute()->fetchCol();
  $nids = array_merge($lead_story_nids, $other_nids);
  if (!empty($nids)) {
    $nodes = Node::loadMultiple($nids);
  }
  if (!empty($nodes)) {
    $variables['groups'] = _revolution_story_groups_render($nodes, 1, TRUE);
  }
}

/*
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__today(&$variables) {
  // Get field values.
  $num_med_img = $variables['paragraph']->field_layout->value;
  $articles = $variables['paragraph']->get('field_articles')->referencedEntities();
  if (!empty($articles)) {
    $variables['groups'] = _revolution_story_groups_render($articles, $num_med_img, FALSE);
  }

  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [];
  $plugin_block = $block_manager->createInstance('sw_recent_articles_block', $config);
  $variables['sw_recent_articles_block_body'] = $plugin_block->build();
}

/**
 * Custom function to decide which view mode should be displayed.
 */
function _revolution_view_mode_image_chooser($i, $med_img_num) {
  if ($i == 0) {
    $view_mode = 'teaser_image_large';
  }
  elseif ($i <= $med_img_num) {
    $view_mode = 'teaser_image_medium';
  }
  else {
    $view_mode = 'teaser';
  }
  return $view_mode;
}

/**
 * Custom function to render the stories into groups.
 *
 * @param array $nodes
 *   Array of fully-loaded node objects.
 * @param integer $num_med_img
 *   The number of medium-sized images to include in the left column.
 * @param boolean $has_right_column
 *   Does the slice we're building use a right column for the stories?
 *
 * @return array
 *   A nested array of slice regions. Keys are region identifiers ('lead',
 *   'column_left' and optionally 'column_right'), values are render arrays to
 *   place in each region.
 */
function _revolution_story_groups_render(array $nodes, $num_med_img, $has_right_column) {
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
  $node_count = count($nodes);
  $i = 0;
  foreach ($nodes as $node) {
    $view_mode = _revolution_view_mode_image_chooser($i, $num_med_img);
    $group_id = _revolution_group_chooser($i, $node_count, $has_right_column);
    if (empty($groups[$group_id]['items'])) {
      $groups[$group_id]['items'] = [];
    }
    $groups[$group_id]['items'][] = $view_builder->view($node, $view_mode);
    $i++;
  }
  foreach ($groups as $group_id => $group) {
    $groups[$group_id]['#prefix'] = '<div class="story-group--' . $group_id . '">';
    $groups[$group_id]['#suffix'] = '</div>';
  }
  return $groups;
}

/**
 * Custom fuction to decide what group to put a given node in.
 *
 * @param integer $i
 *   Index of which story we're considering.
 * @param integer $count
 *   Total number of stories in this slice.
 * @param boolean $has_right_column
 *   Does this slice support a right column for stories at all?
 *
 * @return string
 *   Group identifier for where this story should go.
 */
function _revolution_group_chooser($i, $count, $has_right_column) {
  if ($i == 0) {
    return 'lead';
  }
  if ($has_right_column) {
    // Compute the size of the left column. Remove 1 for the lead story. Then
    // take half of the remaining stories, rounding down, since the comps favor
    // an image in the left side and those take more space.
    $left_size = floor(($count - 1) / 2);
    if ($i > $left_size) {
      return 'column_right';
    }
  }
  return 'column_left';
}

/*
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__reading(&$variables) {

  // Make "What We're Reading" queue/view available in template.
  $view = Views::getView('sw_what_were_reading');
  $view->setDisplay('default');
  $render_array = $view->render();
  $variables['what_were_reading_view'] =  \Drupal::service('renderer')->render($render_array);
}
