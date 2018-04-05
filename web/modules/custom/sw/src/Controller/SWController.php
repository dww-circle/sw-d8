<?php

namespace Drupal\sw\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the SW module.
 */
class SWController extends ControllerBase {

  /**
   * Returns the e-mail alerts signup form page.
   *
   * @return array
   *   Render array for the e-mail alert signup form.
   */
  public function subscribeEmailPage() {
    return [
      '#theme' => 'sw_mailerlite_subscribe_form',
      '#form_type' => 'page',
    ];
  }

}
