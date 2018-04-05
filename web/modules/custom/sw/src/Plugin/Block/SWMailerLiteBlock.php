<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * A site-wide block for the MailerLite (e-mail alerts) subscribe form.
 *
 * @Block(
 *   id = "sw_mailerlite_subscribe_block",
 *   admin_label = @Translation("SW MailerLite subscribe"),
 *   category = @Translation("SW"),
 * )
 */
class SWMailerLiteBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This block can be cached globally, so we don't care about #cache at all.
    return [
      '#theme' => 'sw_mailerlite_subscribe_form',
      '#form_type' => 'block',
    ];
  }
}
