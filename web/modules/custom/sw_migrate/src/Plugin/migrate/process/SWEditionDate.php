<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the 2.0 edition node's title to set the 3.0 static page archive date field.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_edition_date"
 * )
 */
class SWEditionDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $from_date = substr($value, 16); // strlen(''Front page from ')
    try {
      $transformed = DateTimePlus::createFromFormat('F j, Y', $from_date)->format('Y-m-d');
    }
    catch (\InvalidArgumentException $e) {
      throw new MigrateException(sprintf('SW Edition date plugin could not transform "%s" (original: "%s"). Error: %s', $from_date, $value, $e->getMessage()), $e->getCode(), $e);
    }
    catch (\UnexpectedValueException $e) {
      throw new MigrateException(sprintf('SW Edition date plugin could not transform "%s" (original: "%s"). Error: %s', $from_date, $value, $e->getMessage()), $e->getCode(), $e);
    }
    return $transformed;
  }
}
