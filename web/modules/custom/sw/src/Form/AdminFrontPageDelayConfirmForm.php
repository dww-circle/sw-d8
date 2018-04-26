<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminFrontPageDelayConfirmForm extends ConfirmFormBase {

  use SWAdminFrontPageConfirmFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sw_admin_front_page_delay_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to make the draft front page live at midnight?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Move draft to live at midnight');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The draft front page will go live at midnight.'));
    $form_state->setRedirect('sw.admin.content.front_page');
  }

}
