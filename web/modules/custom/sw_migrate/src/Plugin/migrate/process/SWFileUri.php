<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the file url into a D8 compatible URL.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_file_uri"
 * )
 */
class SWFileUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }

    list($filepath, $file_directory_path, $file_timestamp, $img_timestamp) = $value;

    // Strip the files path from the uri instead of using basename so any
    // additional folders in the path are preserved.
    $uri = preg_replace('/^' . preg_quote($file_directory_path, '/') . '/', '', $filepath);
    $path_info = pathinfo($uri);

    // Sanitize the filename.
    $name = sw_clean_filename($path_info['basename']);

    // Finally, build the uri with subdirectories based on the given timestamps.
    // Most files are from the image field which uses an 'images' subdir.
    // Everything else (in the root of the files dir) is a PDF document.
    $parts[] = $path_info['dirname'] === '.' ? 'docs' : ltrim($path_info['dirname'], '/');
    if (!empty($file_timestamp)) {
      $parts[] = date('Y', $file_timestamp);
      $parts[] = date('m', $file_timestamp);
    }
    elseif (!empty($img_timestamp)) {
      $parts[] = date('Y', $img_timestamp);
      $parts[] = date('m', $img_timestamp);
    }
    else {
      $parts[] = 'YYYY/MM';
    }
    $parts[] = $name;
    return 'public://' . implode('/', $parts);
  }

}
