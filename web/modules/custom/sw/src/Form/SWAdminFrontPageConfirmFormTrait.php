<?php

namespace Drupal\sw\Form;

use Drupal\Core\Url;

/**
 * Defines common methods for SW front page admin confirm forms.
 */
trait SWAdminFrontPageConfirmFormTrait {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('sw.admin.content.front_page');
  }

}
