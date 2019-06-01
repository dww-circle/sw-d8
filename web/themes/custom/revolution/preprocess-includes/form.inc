<?php

/*
 * Implements hook_form_FORM_ID_alter().
 */

function revolution_form_search_block_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

  // Add Search Icon.
  $form['icon'] = array( '#markup' => "<i class=\"fas fa-search\"></i>");

}
