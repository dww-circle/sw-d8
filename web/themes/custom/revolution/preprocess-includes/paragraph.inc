<?php
/*
 * Implements hook_preprocess_HOOK().
 */

use Drupal\node\Entity\Node;

function revolution_preprocess_paragraph(&$variables) {

  $paragraph_type = $variables['paragraph']->bundle;

  // Allow preprocess callbacks.
  $functions[] = __FUNCTION__ . '__' . $paragraph_type;

  foreach ($functions as $function) {
    if (function_exists($function)) {
      $function($variables);
    }
  }
}

/*
 * Implements hook_preprocess_FIELD__ENTITY__FIELD_NAME().
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
 * Implements hook_preprocess_FIELD__ENTITY__FIELD_NAME().
 */
function revolution_preprocess_paragraph__today(&$variables) {

  // Get field values.
  $med_img_num = $variables['paragraph']->field_layout->value;
  $count = $variables['content']['field_articles']['#items']->count();

  $i = 0;
  $nodes = array();

  while($i < $count) {

    $nid = $variables['content']['field_articles'][$i]['#plain_text'];
    $view_mode = _revolution_view_mode_image_chooser($i, $med_img_num);
    $nodes[$view_mode][] =  $nid;
    $i++;
  }

  $group = _revolution_story_groups_render($nodes);

  // Send markup to template.
  $variables['groups'] = $group;

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

/*
 * Custom function to render node.
 */
function _revolution_render_node($nid, $view_mode) {
  $entity_type = 'node';
  $node = Node::load($nid);
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
  $build = $view_builder->view($node, $view_mode);
  $output = render($build);

  return $output;
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
