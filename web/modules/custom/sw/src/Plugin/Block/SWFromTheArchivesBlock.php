<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * A block for the story footer region for random 'From the archives' stories.
 *
 * @Block(
 *   id = "sw_from_the_archives_block",
 *   admin_label = @Translation("SW From the archives"),
 *   category = @Translation("SW"),
 * )
 */
class SWFromTheArchivesBlock extends BlockBase {

  use SWTeaserBlockTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'story_list_length' => 5,
      'number_active_stories' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['story_list_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of stories to display in the story list'),
      '#default_value' => isset($config['story_list_length']) ? $config['story_list_length'] : 5,
      '#min' => 1,
    ];

    $form['number_active_stories'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of stories at the top of the queue to pull from'),
      '#default_value' => isset($config['number_active_stories']) ? $config['number_active_stories'] : 20,
      '#description' => $this->t('If set to 0, all stories in the queue are considered active.'),
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    foreach (['number_active_stories', 'story_list_length'] as $config_key) {
      $this->configuration[$config_key] = $values[$config_key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This block must be cached separately for every page/route. Define this
    // render array here so that if we bail early, the render system knows to
    // only cache the empty response for the specific route that generated it.
    $block = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    $config = $this->getConfiguration();
    // Call the procedural helper to do the heavy lifting.
    $stories = sw_pick_from_the_archives_queue(
      $config['story_list_length'],
      $config['number_active_stories']
    );

    // Load and render these stories as teasers.
    return $this->swGetStoryListArray($stories) + $block;
  }
}
