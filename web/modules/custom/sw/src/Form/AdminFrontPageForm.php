<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminFrontPageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sw_admin_front_page';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: Better links for these.
    $urls = [
      '@daily_url' => '/draft/daily',
      '@weekend_url' => '/draft/weekend',
      '@front_url' => '/front',
    ];
    $form['help'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => t('This form lets you clone one of the draft front pages (either <a href="@daily_url">Daily</a> or <a href="@weekend_url">Weekend</a>) to the <a href="@front_url">live front page</a>.', $urls),
      '#suffix' => '</p>',
    ];
    $form['target_draft'] = [
      '#type' => 'select',
      '#title' => t('Which draft page should go live?'),
      '#options' => [
        '/draft/daily' => 'Daily',
        '/draft/weekend' => 'Weekend edition',
      ],
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['delay'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delayed draft to live'),
      '#button_type' => 'primary',
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    // @todo: Disabled the 'delay' button if it's already been submitted.
    $form['actions']['immediate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Immediate draft to live'),
      '#button_type' => 'secondary',
      '#submit' => [
        [$this, 'submitImmediate'],
      ],

    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Submit callback for delayed draft-to-live.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('sw.admin.content.front_page.delay');
  }

  /**
   * Submit callback for immediate draft-to-live.
   */
  public function submitImmediate(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('sw.admin.content.front_page.now');
  }

}
