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
  $med_img_num = $variables['paragraph']->field_num->value;
  $results = $variables['paragraph']->field_results->value;
  $queue_id = $variables['elements']['field_queue'][0]['#plain_text'];

  // Get queue list.
  $entity_subqueue = \Drupal::entityManager()->getStorage('entity_subqueue')->load($queue_id);
  $queue_list = $entity_subqueue->get('items')->getValue();

  $i = 0;
  $output = '';
  foreach($queue_list as $item) {

    // Loop through until field_results value.
    if($i <= $results - 1) {

      // Choose View Mode.
      if ($i == 0) {
        $view_mode = 'teaser_image_large';
      }
      elseif ($i <= $med_img_num) {
        $view_mode = 'teaser_image_medium';
      } else {
        $view_mode = 'teaser';
      }

      //Render Node.
      $nid = $item['target_id'];
      $entity_type = 'node';
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $node = $storage->load($nid);
      $build = $view_builder->view($node, $view_mode);
      $output .= render($build);

      $i++;
    }
  }

  // Send markup to template.
  $variables['rendered_queue'] = $output;

}
