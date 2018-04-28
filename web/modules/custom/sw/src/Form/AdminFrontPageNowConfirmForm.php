<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\sw\DraftToLive;

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
    $placeholders = $this->getSiteStatePlaceholders();
    if (!empty($placeholders['%target'])) {
      $cancel_txt = '<div class="messages messages--warning">'
                  . $this->t('There is a draft-to-live for %target scheduled by %account that will be canceled if you proceed.', $placeholders)
                  . '</div>';
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
    $placeholders = $this->getSiteStatePlaceholders();
    if (!empty($placeholders['%target'])) {
      $this->deleteSiteState();
      drupal_set_message($this->t('Canceled the draft-to-live for %target scheduled by %account', $placeholders), 'warning');
    }
    $data = $this->getTempStoreData();
    $this->deleteTempStoreData();
    $draft_to_live = new DraftToLive($data['target_draft'], $this->currentUser()->id());
    $draft_to_live->execute(TRUE);
    $form_state->setRedirect('<front>');
  }

}
