<?php

use \Drupal\Core\Url;

function revolution_preprocess_forward(&$variables) {
  $variables['front_page'] = Url::fromUri('internal:/', ['absolute' => TRUE])->toString();
  $theme_path = $variables['front_page'] . $variables['directory'];
  $variables['email_css_path'] =  $theme_path . '/css/email.css';
  $variables['logo'] = '<img src="' . $theme_path . '/images/logos/logo.svg" />';
}
