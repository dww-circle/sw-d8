<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SearchBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sw_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#title' => $this->t('Text'),
      '#type' => 'textfield',
      '#size' => '10',
      '#maxlength' => '128',
    ];
    $form['actions']['search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $redirect = Url::fromUri('internal:/search', ['query' => ['text' => $form_state->getValue('text')]]);
    $form_state->setRedirectUrl($redirect);
  }

}
