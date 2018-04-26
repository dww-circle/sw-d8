<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

class AdminFrontPageNowConfirmForm extends ConfirmFormBase {

  use AdminFrontPageStateTrait;
  use AdminFrontPageConfirmFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sw_admin_front_page_now_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $data = $this->getTempStoreData();
    return $this->t('Are you sure you want to make the @target front page live immediately?', ['@target' => $data['target_draft']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $cancel_txt = '';
    $state = $this->getSiteState();
    if (!empty($state['sw_front_page_target_draft'])) {
      $placeholders = [
        '%target' => $state['sw_front_page_target_draft'],
        // @todo: Load user and print with a label?
        '%account' => $state['sw_front_page_request_uid'],
      ];
      $cancel_txt = '<p>' . $this->t('This is a draft-to-live for %target scheduled by %account that will be canceled if you proceed.', $placeholders) . '</p>';
    }
    return $this->getBaseDescription()
      . $cancel_txt
      . '<p>' . $this->t('This operation cannot be undone.') . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $data = $this->getTempStoreData();
    return $this->t('Move @target to live right NOW', ['@target' => $data['target_draft']]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = $this->getSiteState();
    if (!empty($state['sw_front_page_target_draft'])) {
      $this->deleteSiteState();
      $placeholders = [
        '%target' => $state['sw_front_page_target_draft'],
        '%account' => $state['sw_front_page_request_uid'],
      ];
      drupal_set_message($this->t('Canceled the draft-to-live for %target scheduled by %account', $placeholders), 'warning');
    }
    $data = $this->getTempStoreData();
    drupal_set_message($this->t('Initiating immediate draft-to-live for %target!', ['%target' => $data['target_draft']]));
    // @todo actual draft to live code.
    $this->deleteTempStoreData();
    $form_state->setRedirect('<front>');
  }

}
