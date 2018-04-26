<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines shared code used for SW front page administration confirm forms.
 */
trait AdminFrontPageConfirmFormTrait {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('sw.admin.content.front_page');
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseDescription() {
    $data = $this->getTempStoreData();
    return
      '<p>' .
      $this->t('Ready to clone and publish %target to the live front page?',
               ['%target' => $data['target_draft']])
      . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->getCancelText(),
      '#submit' => [
        [$this, 'cancelForm'],
      ],
    ];
    return $form;
  }

  /**
   * Submit callback to cancel the confirm form.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $this->deleteTempstoreData();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
