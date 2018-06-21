<?php

namespace Drupal\sw;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class that contains all the logic for the word-export tab on stories.
 */
class StoryWordExporter {

  /**
   * The story to generate a word export for.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $story;

  /**
   * Constructor.
   *
   * @param \Drupal\node\NodeInterface $story
   *   The story to generate a word export for.
   */
  public function __construct($story) {
    $this->story = $story;
  }

  /**
   * Builds the word export output.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response with the word export output.
   */
  public function build() {
    $section_break = "\n<p>- - - - - - - - - - - - - - - - -</p>\n";
    $allowed_tags = '<div><p><br><em><i><cite><b><strong><hr><blockquote><h1><h2><h3><ul><ol><li>';

    // @todo This should probably be an injected dependency.
    $renderer = \Drupal::service('renderer');

    sw_load_story_people($this->story);
    // Lie about the view mode so that we get a plain text story label.
    $story_label = sw_get_story_label($this->story, 'teaser');
    $output = '<div class="story-label">' . $story_label . "</div>\n";
    $kicker = $this->story->get('field_kicker')->value;
    if (!empty($kicker)) {
      $output .= '<div class="kicker">' . $kicker . "</div>\n";
    }
    $output .= '<h1 class="title headline">' . $this->story->label() . "</h1>\n";
    $output .= '<p class="dateline">' . format_date($this->story->get('created')->value, 'medium') . "</p>\n";
    $output .= $section_break;

    foreach (['field_introduction', 'field_body_introduction'] as $field) {
      $value = $this->story->get($field)->getValue();
      if (!empty($value)) {
        $output .= check_markup(sw_replace_people_tokens($this->story, $value[0]['value'], FALSE), $value[0]['format']) . $section_break;
      }
    }

    // We need to do some special processing on the body field.
    $value = $this->story->get('field_body')->getValue();
    // First, remember if there's a magic series insert box.
    $has_series_insert = (strstr($value[0]['value'], '<sw-series>') !== FALSE);
    // Also, see if there are any embedded nodes (insert boxes).
    $matches = [];
    if (preg_match_all('@<drupal-entity data-entity-type="node" data-entity-id="(\d+)"@', $value[0]['value'], $matches)) {
      $embedded_nodes = $matches[1];
    }
    // Strip all tags other than whitespace and italic/bold formatting before we
    // call check_markup(). This way, all the embed-related tags will already be
    // gone before we try to actually embed anything.
    $output .= check_markup(strip_tags($value[0]['value'], $allowed_tags), $value[0]['format']);

    // Handle story contributors (if any).
    sw_load_referenced_entities($this->story, 'contributors');
    if (!empty($this->story->sw_contributors)) {
      uasort($this->story->sw_contributors, 'sw_contributor_sort');
      $output .= '<div class="contributors" style="font-style: italic;">' . sw_get_entity_label_multiple($this->story->sw_contributors, 'sw_plain_label') . t(' contributed to this article.') . '</div>';
    }

    // Append any insert boxes at the bottom of the page.
    if ($has_series_insert || !empty($embedded_nodes)) {
      $insert_break = "<p>= = = = = = = = = = = = = = =</p>\n";
    }
    if ($has_series_insert) {
      $series_insert = sw_insert_series_box($this->story);
      $output .= $insert_break . $renderer->render($series_insert);
    }
    if (!empty($embedded_nodes)) {
      // @todo This should probably be an injected dependency.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      foreach ($embedded_nodes as $nid) {
        $node = Node::load($nid);
        $node_output = $view_builder->view($node, 'full');
        $output .= $insert_break . $renderer->render($node_output);
      }
    }

    // Instead of having blockquote rendered as such, we want to have the tags
    // appear in the output.
    $output = preg_replace(['@<blockquote>@', '@</blockquote>@'], ['[[BLOCKQUOTE]]', '[[ENDQUOTE]]'], $output);

    // Strip all remaining tags, other than italics/bold and whitespace formatting.
    $output = strip_tags($output, $allowed_tags);

    return new Response($output);
  }

}
