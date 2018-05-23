<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * Harvest the raw HTML from the 2.0 legacy front page archive for 3.0 static pages.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_edition_body"
 * )
 */
class SWEditionBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // @todo Be more careful with $value, ensure it's a nid, etc.
    $url = "https://socialistworker.org/node/$value";
    $client = \Drupal::httpClient();
    try {
      $request = $client->get($url);
      $status = $request->getStatusCode();
    }
    catch (ConnectException $e) {
      throw new MigrateException(sprintf('Connect failed for %s error: %s', $url, $e->getMessage()), $e->getCode(), $e);
    }
    catch (RequestException $e) {
      throw new MigrateException(sprintf('Request failed for %s status: %d error: %s', $url, $status, $e->getMessage()), $e->getCode(), $e);
    }
    catch (RequestException $e) {
      throw new MigrateException(sprintf('Other exception while fetching %s error: %s', $url, $e->getMessage()), $e->getCode(), $e);
    }
    return [
      'value' => str_replace(
        [
          'https://socialistworker.org/sites/default/files/css',
          'https://socialistworker.org/sites/default/files/js',
          'https://socialistworker.org/sites/default/files/imagecache',
        ],
        [
          '/sites/default/files/archive/css',
          '/sites/default/files/archive/js',
          '/sites/default/files/imagecache',
        ],
        $request->getBody()->getContents()
      ),
      'format' => 'full_html',
    ];
  }
}
