<?php

namespace Drupal\sw\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Base class for SW blocks involving recent articles.
 */
abstract class SWRecentArticlesBase extends BlockBase {

  /**
   * Static array of the 6 most recent dates that have published stories.
   *
   * This is shared across all instances of children classes so we don't
   * re-run the same queries.
   */
  static protected $swRecentPubDates = [];

  /**
   * Static nested array of all published articles from the last 6 days.
   *
   * Parent array is indexed (and ordered) by the date id (YYYYMMDD).
   * Children arrays are indexed by the story nid, ordered by story_weight
   * (DESC) and contain objects for each article.  Each object has the values
   * nid, vid, title, story_weight, and created_day.
   *
   * This is shared across all instances of children classes so we don't
   * re-run the same queries.
   */
  static protected $swRecentStories = [];

  /**
   * Helper function to find the 6 most recent days that SW published articles.
   *
   * Technically, this is all wrong, and not the Proper Drupal 8 Way(tm). It
   * assumes MySQL. It assumes entities are stored in the DB, not some fancy
   * plugable backend, etc. Thankfully, all these assumptions are true.
   *
   * @return array
   *   Array of publication dates, of the form YYYYMMDD.
   */
  protected function getRecentPublicationDates() {
    if (empty(SWRecentArticlesBase::$swRecentPubDates)) {
      $query = \Drupal::database()->query("SELECT DATE_FORMAT((DATE_ADD('19700101', INTERVAL node_field_data.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') AS node_field_data_created_day FROM node_field_data node_field_data WHERE (node_field_data.status = '1') AND (node_field_data.type IN ('story')) GROUP BY node_field_data_created_day ORDER BY node_field_data_created_day DESC LIMIT 6;");
      SWRecentArticlesBase::$swRecentPubDates = $query->fetchCol();
    }
    return SWRecentArticlesBase::$swRecentPubDates;
  }

  /**
   * Helper function to find all the articles published on the given dates.
   *
   * Technically, this is all wrong, and not the Proper Drupal 8 Way(tm). It
   * assumes MySQL. It assumes entities are stored in the DB, not some fancy
   * plugable backend, etc. Thankfully, all these assumptions are true.
   *
   * @return array
   *   Nested array of all published articles from the last 6 days.
   *   Parent array is indexed by the date id (YYYYMMDD), sorted by created_day (DESC).
   *   Children arrays are indexed by the story nid, sorted by story_weight (ASC).
   *   Each object has the values nid, vid, title, story_weight, and created_day.
   */
  protected function findRecentArticles() {
    if (empty(SWRecentArticlesBase::$swRecentStories)) {
      $dates = $this->getRecentPublicationDates();
      if (!empty($dates)) {
        $query = \Drupal::database()->query(
          "SELECT nfd.nid, nfd.vid, nfd.title, nfsw.field_story_weight_value AS story_weight, DATE_FORMAT((DATE_ADD('19700101', INTERVAL nfd.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') AS created_day FROM {node_field_data} nfd INNER JOIN {node__field_story_weight} nfsw ON nfd.nid = nfsw.entity_id WHERE (nfd.status = '1') AND (nfd.type = 'story') AND DATE_FORMAT((DATE_ADD('19700101', INTERVAL nfd.created SECOND) + INTERVAL -18000 SECOND), '%Y%m%d') IN ( :days[] ) ORDER BY created_day DESC, story_weight ASC", [':days[]' => $dates]);
        $articles = $query->fetchAllAssoc('nid');
        foreach ($articles as $nid => $article) {
          SWRecentArticlesBase::$swRecentStories[$article->created_day][$nid] = $article;
        }
      }
    }
    return SWRecentArticlesBase::$swRecentStories;
  }

}
