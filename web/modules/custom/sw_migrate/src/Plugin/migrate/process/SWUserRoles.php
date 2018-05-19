<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Map SW 2.0 users to the 3.0 roles.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_user_roles"
 * )
 */
class SWUserRoles extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    switch ($value) {
      case 'root':
      case 'maass':
      case 'colson':
      case 'schulte':
      case 'yanowitz':
        return [
          'reading_editor',
          'story_editor',
          'front_page_editor',
        ];

      case 'bailey':
      case 'derek':
      case 'dorian':
      case 'erickerl':
      case 'katch':
      case 'khury':
      case 'phil':
      case 'ruder':
        return [
          'reading_editor',
          'story_editor',
        ];

      case 'cohen':
      case 'elaine':
      case 'jeremy':
      case 'john':
      case 'josh':
      case 'kelly':
      case 'lenzo':
      case 'sustar':
      case 'vauxia':
      default:
        return [];

    }
  }

}
