<?php
/*
 * Implements hook_preprocess_HOOK().
 */

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
  $mid_img_num = $variables['paragraph']->field_num->value;
  $results = $variables['paragraph']->field_results->value;
  $queue_id = $variables['elements']['field_queue'][0]['#plain_text'];

  // Get queue values.
  $entity_subqueue = \Drupal::entityManager()->getStorage('entity_subqueue')->load($queue_id);
  $queue_nids = $entity_subqueue->get('items')->getValue();

  $i = 0;
  $output = '';

  foreach($queue_nids as $nid) {

    if($i != $results) {

      // Choose View Mode.
      if ($i == 0) {
        $view_mode = 'teaser_image_large';
      }

      else {
        $view_mode = 'teaser';
      }
    }

    $nid = $nid['target_id'];

    //Render Node.
    $entity_type = 'node';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $node = $storage->load($nid);
    $build = $view_builder->view($node, $view_mode);
    $output .= render($build);

    $i++;
  }

  $variables['rendered_queue'] = $output;
  
}
