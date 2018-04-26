<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;

class AdminFrontPageForm extends FormBase {

  use AdminFrontPageStateTrait;

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
    $urls = [
      ':daily_url' => Url::fromUri('internal:/draft/daily')->toString(),
      ':weekend_url' => Url::fromUri('internal:/draft/weekend')->toString(),
      ':front_url' => Url::fromRoute('<front>')->toString(),
    ];
    $form['help'] = [
      '#weight' => '-4',
      '#prefix' => '<p>',
      '#markup' => t('This form lets you clone one of the draft front pages (either <a href=":daily_url">Daily</a> or <a href=":weekend_url">Weekend</a>) to the <a href=":front_url">live front page</a>.', $urls),
      '#suffix' => '</p>',
    ];
    $form['target_draft'] = [
      '#type' => 'select',
      '#title' => t('Which draft page should go live?'),
      '#options' => [
        '/draft/daily' => 'Daily',
        '/draft/weekend' => 'Weekend',
      ],
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['delay'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delayed draft-to-live'),
      '#button_type' => 'primary',
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    $state = $this->getSiteState();
    if (!empty($state['sw_front_page_target_draft'])) {
      $placeholders = $this->getSiteStatePlaceholders();
      $form['help_delay'] = [
        '#weight' => -2,
        '#markup' => $this->t('Draft-to-live for %target has been scheduled to run at midnght by %account.', $placeholders),
      ];
      $form['target_draft']['#default_value'] = $state['sw_front_page_target_draft'];
      $form['actions']['delay']['#disabled'] = TRUE;
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel delayed draft-to-live'),
        '#button_type' => 'danger',
        '#submit' => [
          [$this, 'cancelDelay'],
        ],
      ];
    }
    $form['actions']['immediate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Immediate draft-to-live'),
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
    $this->saveTempState($form_state);
    $form_state->setRedirect('sw.admin.content.front_page.delay');
  }

  /**
   * Submit callback for immediate draft-to-live.
   */
  public function submitImmediate(array &$form, FormStateInterface $form_state) {
    $this->saveTempState($form_state);
    $form_state->setRedirect('sw.admin.content.front_page.now');
  }

  /**
   * Submit callback to cancel a pending delayed draft-to-live.
   */
  public function cancelDelay(array &$form, FormStateInterface $form_state) {
    $placeholders = $this->getSiteStatePlaceholders();
    $this->deleteSiteState();
    drupal_set_message($this->t('Canceled the delayed draft-to-live for %target scheduled by %account.', $placeholders));
  }

  /**
   * Save useful values from the form state into the per-user temp storage.
   */
  protected function saveTempState(FormStateInterface $form_state) {
    $values = [
      'target_draft' => $form_state->getValue('target_draft'),
    ];
    $this->setTempStoreData($values);
  }
}
