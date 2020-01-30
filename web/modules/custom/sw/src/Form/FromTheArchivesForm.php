<?php

namespace Drupal\sw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;

class FromTheArchivesForm extends FormBase {

  protected $subqueue;

  /**
   * Constructs a FromTheArchivesForm instance.
   */
  public function __construct() {
    $this->subqueue = EntitySubqueue::load('from_the_archives');
    assert(!empty($this->subqueue));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sw_from_the_archives_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#weight' => '-4',
      '#prefix' => '<p>',
      '#markup' => t('This form lets you add story NIDs in bulk to the <a href=":url">From The Archives</a> entity queue.', [':url' => $this->subqueue->toUrl('edit-form')->toString()]),
      '#suffix' => '</p>',
    ];
    $form['nids'] = [
      '#type' => 'textarea',
      '#title' => t('NIDs'),
      '#description' => t('Put each NID on a separate line.'),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to <em>From The Archives</em> entity queue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
      '#validate' => ['::validateForm'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $valid_nids = [];
    $nids = preg_split("/\\r\\n|\\r|\\n/", $form_state->getValue('nids'));

    // Create a map of all existing items in the queue to check for duplicates.
    $existing_nids = [];
    $items = $this->subqueue->get('items')->getValue();
    foreach ($items as $item) {
      $existing_nids[$item['target_id']] = $item['target_id'];
    }

    foreach ($nids as $nid) {
      $nid = trim($nid);
      if (empty($nid)) {
        continue;
      }
      if (!is_numeric($nid)) {
        $form_state->setErrorByName('nids', t('The value entered %value is not a number. Please try again.', ['%value' => $nid]));
        continue;
      }
      $story = Node::load($nid);
      if (empty($story) || $story->bundle() !== 'story') {
        $form_state->setErrorByName('nids', t('The NID %nid is not a valid story. Please try again.', ['%nid' => $nid]));
        continue;
      }

      if (!empty($existing_nids[$nid])) {
        $this->messenger()->addWarning(t('NID %nid @story is already in the queue, ignoring.', ['%nid' => $nid, '@story' => $story->toLink()->toString()]));
        continue;
      }
      $valid_nids[] = $nid;
    }
    $form_state->setValue('valid_nids', $valid_nids);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valid_nids = $form_state->getValue('valid_nids');

    $stories = Node::loadMultiple($valid_nids);
    foreach ($stories as $story) {
      $this->subqueue->addItem($story);
      $this->messenger()->addStatus(t('Added @label', ['@label' => $story->toLink()->toString()]));
    }
    $this->subqueue->save();
  }

}
