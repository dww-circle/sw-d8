<?php
/*
 * Implements hook_preprocess_HOOK().
 */

use Drupal\node\Entity\Node;
use Drupal\views\Views;

/*
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__queue(&$variables) {

  // Get field values.
  $med_img_num = $variables['paragraph']->field_num->value;
  $results = $variables['paragraph']->field_results->value;
  $queue_id = $variables['elements']['field_subqueue'][0]['#plain_text'];

  // Get queue list.
  $entity_subqueue = \Drupal::entityManager()->getStorage('entity_subqueue')->load($queue_id);

  if ($queue_id != null) {
    $queue_list = $entity_subqueue->get('items')->getValue();
  }

  $i = 0;
  $nodes = array();

  foreach($queue_list as $item) {

    // Loop through until field_results value.
    if($i <= $results - 1) {

      $nid = $item['target_id'];
      $view_mode = _revolution_view_mode_image_chooser($i, $med_img_num);
      $nodes[$view_mode][] =  $nid;

      $i++;
    }
  }

  $group = _revolution_story_groups_render($nodes);

  // Send markup to template.

  $variables['groups'] = $group;

}

/*
 * Implements hook_preprocess_paragraph__TYPE().
 */
function revolution_preprocess_paragraph__today(&$variables) {

  // Get field values.
  $med_img_num = $variables['paragraph']->field_layout->value;

  if (!empty($variables['content']['field_articles']['#items'])) {
    $count = $variables['content']['field_articles']['#items']->count();
    $nodes = [];
    for ($i = 0; $i < $count; $i++) {
      if (!empty($variables['content']['field_articles'][$i]['#plain_text'])) {
        $nid = $variables['content']['field_articles'][$i]['#plain_text'];
        $view_mode = _revolution_view_mode_image_chooser($i, $med_img_num);
        $nodes[$view_mode][] = $nid;
      }
    }

    $group = _revolution_story_groups_render($nodes);
    // Send markup to template.
    $variables['groups'] = $group;
  }

  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [];
  $plugin_block = $block_manager->createInstance('sw_recent_articles_block', $config);
  $variables['sw_recent_articles_block_body'] = $plugin_block->build();
}

/*
 * Custom function to decide which view mode should be displayed.
 */
function _revolution_view_mode_image_chooser($i, $med_img_num) {
  // Choose View Mode.
  if ($i == 0) {
    $view_mode = 'teaser_image_large';
  }
  elseif ($i <= $med_img_num) {
    $view_mode = 'teaser_image_medium';
  } else {
    $view_mode = 'teaser';
  }

  return $view_mode;
}

/**
 * Custom function to render node.
 */
function _revolution_render_node($nid, $view_mode) {
  $node = Node::load($nid);
  if (!empty($node)) {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $view_builder->view($node, $view_mode);
    return render($build);
  }
}

/*
 * Custom function to render the grouped stories.
 */
function _revolution_story_groups_render($nodes) {

  $group = array();

  foreach ($nodes as $view_mode => $nids) {
    $group_markup = '<div class="story-group--' . $view_mode . '">';
    foreach($nids as $nid) {
      $group_markup .= _revolution_render_node($nid, $view_mode);
    }
    $group_markup .= '</div>';

    $group[$view_mode] = $group_markup;
  }

  return $group;
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
