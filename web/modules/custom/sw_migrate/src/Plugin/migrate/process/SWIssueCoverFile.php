<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the file url into a D8 compatible URL.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_issue_cover_file"
 * )
 */
class SWIssueCoverFile extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($issue_number, $fid) = $value;
    if (empty($issue_number) || empty($fid)) {
      return [];
    }
    return [
      'target_id' => $fid,
      'display' => TRUE,
      'description' => '',
      'alt' => "Socialist Worker print issue #$issue_number cover image.",
      'title' => '',
    ];
  }
}
