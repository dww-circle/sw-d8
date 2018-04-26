<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

class AdminFrontPageDelayConfirmForm extends ConfirmFormBase {

  use AdminFrontPageStateTrait;
  use AdminFrontPageConfirmFormTrait;

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
    $data = $this->getTempStoreData();
    return $this->t('Are you sure you want to make the @target front page live at midnight?', ['@target' => $data['target_draft']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getBaseDescription()
      . '<p>' . $this->t('This can only be canceled between now and midnight.') . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $data = $this->getTempStoreData();
    return $this->t('Move @target to live at midnight', ['@target' => $data['target_draft']]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $this->getTempStoreData();
    $this->deleteTempStoreData();
    $values = [
      'sw_front_page_target_draft' => $data['target_draft'],
      'sw_front_page_request_uid' => $this->currentUser()->id(),
    ];
    $this->setSiteState($values);
    drupal_set_message($this->t('The %target front page will go live at midnight.', ['%target' => $data['target_draft']]));
    $form_state->setRedirect('sw.admin.content.front_page');
  }

}
