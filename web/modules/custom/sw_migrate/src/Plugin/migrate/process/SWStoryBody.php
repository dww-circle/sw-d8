<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the body field for SW story nodes.
 *
 * - Rewrites long chains of '- - -'... into <hr> tags.
 * - Rewrites <dme> tags into <drupal-entity> tags and moves them down to the
 *   first paragraph break 1000 characters or more below where they are now.
 * - Avoids placing <drupal-entity> tags inside a <blockquote>.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_story_body"
 * )
 */
class SWStoryBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Replace 3 or more instances of '- ' and an optional trailing '-' with '<hr>':
    $value = preg_replace('#(- ){3,}(-)?#', '<hr>', $value);

    // Split the body into an array for easier DME tag re-positioning.
    // We split on 2 line breaks (\R matches \n, \r or \r\n).
    // We don't want to split on single line breaks, since for example, we
    // want to keep all this together as a single paragraph:
    // <blockquote>
    // Whatever the quote is.
    // </blockquote>
    $sw2_body = preg_split('/(\R){2}/', $value);

    // Build up the array of paragraphs we want for the 3.0 body.
    $sw3_body = [];
    $pending_embed_tags = [];
    $current_embed_offset = 0;
    $inside_blockquote = FALSE;
    foreach ($sw2_body as $para) {
      $matches = [];
      if (preg_match('@<dme:(box|img|series)[^>]*>@', $para, $matches)) {
        switch ($matches[1]) {
          case 'box':
            $pending_embed_tags[] = $this->rewriteDMEBox($para);
            break;

          case 'img':
            $pending_embed_tags[] = $this->rewriteDMEImg($para);
            break;

          case 'series':
            // @todo Are we going to re-write these?
            $pending_embed_tags[] = $para;
            break;
        }
      }
      else {
        // If a paragraph begins with optional whitespace, exactly two
        // hyphens, and more optional whitespace, replace all that with the
        // bullet point span and a single space.
        $sw3_body[] = preg_replace('#^(\s*--\s*)#', '<span class="bullet"></span> ', $para);
        // Count the real characters (not include HTML tags) of this paragraph.
        $current_embed_offset += strlen(strip_tags($para));
        // See if this paragraph is opening a blockquote.
        if (strpos($para, '<blockquote>') !== FALSE) {
          $inside_blockquote = TRUE;
        }
        // If it's a 1 paragraph quote that closes it, or if it's the end of a
        // multi-paragraph blockquote, we're no longer inside a blockquote.
        if (strpos($para, '</blockquote>') !== FALSE) {
          $inside_blockquote = FALSE;
        }
      }
      if (!$inside_blockquote && $current_embed_offset >= 1000 && count($pending_embed_tags) > 0) {
        $sw3_body[] = array_shift($pending_embed_tags);
        $current_embed_offset = 0;
      }
    }
    // See if we have any pending tags we haven't inserted.
    if (!empty($pending_embed_tags)) {
      // Oh shit. Stuff any remaining embed tags into the end of the body.
      foreach ($pending_embed_tags as $embed_tag) {
        $sw3_body[] = $embed_tag;
      }
      // @todo Log a warning message about this.
    }
    return implode("\r\n\r\n", $sw3_body);
  }

  /**
   * Helper function to re-write SW 2.0 DME box tags into <drupal-embed>.
   *
   * @param string $dme_box
   *   The SW 2.0 DME box tag to re-write.
   *
   * @return string
   *   The appropriate <drupal-embed> tag for SW 3.0.
   */
  protected function rewriteDMEBox($dme_box) {
    $nid = [];
    if (preg_match('@<dme:box nid=(\d+)[^>]*>@', $dme_box, $nid)) {
      return '<drupal-entity data-entity-type="node" data-entity-id="'
        . $nid[1] . '" data-view-mode="default"></drupal-entity>';
    }
  }

  /**
   * Helper function to re-write SW 2.0 DME img tags into <drupal-embed>.
   *
   * @param string $dme_img
   *   The SW 2.0 DME img tag to re-write.
   *
   * @return string
   *   The appropriate <drupal-embed> tag for SW 3.0.
   */
  protected function rewriteDMEImg($dme_img) {
    $embed_attrs = [];
    $nid = [];
    if (preg_match('@<dme:img nid=(\d+)[^>]*>@', $dme_img, $nid)) {
      $embed_attrs[] = 'data-entity-type="media"';
      $embed_attrs[] = 'data-entity-id="' . $nid[1] . '"';
      $view_mode = 'embed';
      $size = [];
      if (preg_match('@.*size=(\d+)@', $dme_img, $size)) {
        if ($size[1] > 425) {
          $view_mode = 'embed_wide';
        }
      }
      $embed_attrs[] = 'data-view-mode="' . $view_mode . '"';
      $caption = [];
      if (preg_match('@.*caption="([^"]+)"@', $dme_img, $caption)) {
        $embed_attrs[] = 'data-caption="' . $caption[1] . '"';
      }
      return '<drupal-entity ' . implode(' ', $embed_attrs) . '></drupal-entity>';
    }
  }

}
