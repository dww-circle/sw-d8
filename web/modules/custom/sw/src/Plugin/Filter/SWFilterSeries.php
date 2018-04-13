<?php

namespace Drupal\sw\Plugin\Filter;

use Drupal\Core\Routing;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\node\NodeInterface;

/**
 * Provides a filter to replace <sw-series> tags with the magic series insert box.
 *
 * @Filter(
 *   id = "sw_filter_series",
 *   title = @Translation("SW series insert box"),
 *   description = @Translation("Replaces <code>&lt;sw-series&gt;</code> tags with an automagic series insert box."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class SWFilterSeries extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (stristr($text, 'sw-series') !== FALSE) {
      $series_box = '';
      $node = \Drupal::routeMatch()->getParameter('node');
      if (!empty($node) && $node->bundle() == 'story') {
        $series_render = sw_insert_series_box($node);
        $series_box = \Drupal::service('renderer')->render($series_render);
      }
      $result->setProcessedText(preg_replace('@<sw-series></sw-series>@', $series_box, $text));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Use <code>&lt;sw-series&gt;&lt;/sw-series&gt;</code> for an automagic series insert box.');
  }

}
