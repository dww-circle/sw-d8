<?php

namespace Drupal\sw\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * Provides a filter to caption elements.
 *
 * When used in combination with the filter_align filter, this must run last.
 *
 * This code is copied from the core FilterCaption plugin, but has logic to
 * handle a special case where the caption is set to "default". In that case,
 * we find the entity_id of the thing being embedded, and call a helper
 * function to get the appropriate caption to use.
 *
 * @Filter(
 *   id = "sw_filter_caption",
 *   title = @Translation("Caption SW images"),
 *   description = @Translation("Uses a <code>data-caption</code> attribute on <code>&lt;img&gt;</code> tags to caption images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class SWFilterCaption extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-caption') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-caption]') as $node) {
        // Read the data-caption attribute's value, then delete it.
        $caption = $node->getAttribute('data-caption');
        $node->removeAttribute('data-caption');

        // If the caption is set to "default", pull the caption from the entity.
        if ($caption === 'default') {
          $entity_id = $node->getAttribute('data-entity-id');
          $caption = sw_get_media_caption($entity_id);
          $node->removeAttribute('data-entity-id');
        }

        $caption = Html::escape($caption);

        // Sanitize caption: decode HTML encoding, limit allowed HTML tags; only
        // allow inline tags that are allowed by default, plus <br>.
        $caption = Html::decodeEntities($caption);
        $caption = FilteredMarkup::create(Xss::filter($caption, ['a', 'em', 'strong', 'cite', 'code', 'br']));

        // The caption must be non-empty.
        if (Unicode::strlen($caption) === 0) {
          continue;
        }

        // Given the updated node and caption: re-render it with a caption, but
        // bubble up the value of the class attribute of the captioned element,
        // this allows it to collaborate with e.g. the filter_align filter.
        $tag = $node->tagName;
        $classes = $node->getAttribute('class');
        $node->removeAttribute('class');
        $node = ($node->parentNode->tagName === 'a') ? $node->parentNode : $node;
        $filter_caption = [
          '#theme' => 'filter_caption',
          // We pass the unsanitized string because this is a text format
          // filter, and after filtering, we always assume the output is safe.
          // @see \Drupal\filter\Element\ProcessedText::preRenderText()
          '#node' => FilteredMarkup::create($node->C14N()),
          '#tag' => $tag,
          '#caption' => $caption,
          '#classes' => $classes,
        ];
        $altered_html = \Drupal::service('renderer')->render($filter_caption);

        // Load the altered HTML into a new DOMDocument and retrieve the element.
        $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
          ->item(0)
          ->childNodes;

        foreach ($updated_nodes as $updated_node) {
          // Import the updated node from the new DOMDocument into the original
          // one, importing also the child nodes of the updated node.
          $updated_node = $dom->importNode($updated_node, TRUE);
          $node->parentNode->insertBefore($updated_node, $node);
        }
        // Finally, remove the original data-caption node.
        $node->parentNode->removeChild($node);
      }

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments([
          'library' => [
            'filter/caption',
          ],
        ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can caption images, videos, blockquotes, and so on. Examples:</p>
        <ul>
            <li><code>&lt;img src="" data-caption="This is an img caption" /&gt;</code></li>
            <li><code>&lt;video src="" data-caption="This is a video caption" /&gt;</code></li>
            <li><code>&lt;blockquote data-caption="Alan Maass"&gt;SocialistWorker.org is so great!&lt;/blockquote&gt;</code></li>
            <li><code>&lt;drupal-entity data-entity-id="1234" data-caption="Something custom for this entity"&gt;&lt;/drupal-entity&gt;</code></li>
            <li><code>&lt;drupal-entity data-entity-id="2345" data-caption="default"&gt;&lt;/drupal-entity&gt;</code> (looks up the caption from the referenced entity</li>
        </ul>');
    }
    else {
      return $this->t('You can caption images (<code>data-caption="Text"</code>), but also videos, blockquotes, and so on.');
    }
  }

}
