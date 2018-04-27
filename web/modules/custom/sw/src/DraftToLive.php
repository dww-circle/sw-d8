<?php

namespace Drupal\sw;

use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * Class that contains all the logic for the magic draft-to-live operation.
 */
class DraftToLive {

  /**
   * The target draft front page to push live.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $targetDraft;

  /**
   * The User ID (UID) of the user who requested the draft-to-live operation.
   *
   * @var integer
   */
  protected $requestUID;

  /**
   * The fully loaded Node object for the live front page.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $liveFrontPageNode;

  /**
   * The fully loaded Node object for the target draft front page.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $draftFrontPageNode;

  /**
   * The alias storage service object.
   *
   * @var \Drupal\Core\Path\AliasStorage
   */
  protected $aliasStorage;

  /**
   * Constructor.
   *
   * @param string $target_draft
   *   The target draft front page to push live.
   * @param integer $request_uid
   *   The User ID (UID) of the user who requested the draft-to-live operation.
   */
  public function __construct($target_draft, $request_uid) {
    $this->targetDraft = $target_draft;
    $this->requestUID = $request_uid;
    // @todo Maybe this should all be dependency injection fun.
    $this->aliasStorage = \Drupal::service('path.alias_storage');
  }

  /**
   * Execute all the draft-to-live operations.
   *
   * @param boolean $verbose
   *   Optional flag to control if the operations should print verbose messages to the screen.
   */
  public function execute($verbose = FALSE) {
    $this->initializeFrontPages();
    $this->archiveCurrentFrontPage($verbose);
    $this->cloneDraftToLive($verbose);
  }

  /**
   * Load a node object with a given URL path alias.
   *
   * @param string $path_alias
   *   The URL path alias to search for.
   *
   * @return \Drupal\node\Entity\Node
   *   The fully loaded node object with the given alias, or NULL if not found.
   */
  protected function loadNodeFromAlias($path_alias) {
    $alias = $this->aliasStorage->load(['alias' => $path_alias]);
    if (!empty($alias)) {
      $matches = [];
      if (preg_match('@/node/(\d+)@', $alias['source'], $matches)) {
        return Node::load($matches[1]);
      }
    }
  }

  protected function initializeFrontPages() {
    $this->liveFrontPageNode = $this->loadNodeFromAlias('/live');
    $this->draftFrontPageNode = $this->loadNodeFromAlias($this->targetDraft);
  }

  /**
   * Archive the current front page.
   *
   * @param boolean $verbose
   *   Optional flag to control printing verbose messages to the screen.
   */
  protected function archiveCurrentFrontPage($verbose = FALSE) {
    $client = \Drupal::httpClient();
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    $paragraphs = $this->liveFrontPageNode->get('field_slices')->referencedEntities();
    // Can't assume the slice we care about is first, since there might be a
    // banner ad or something.
    foreach ($paragraphs as $paragraph) {
      $paragraph_type = $paragraph->getType();
      if ($paragraph_type == 'today' || $paragraph_type == 'weekend') {
        $date = $paragraph->get('field_date')->value;
        break;
      }
    }
    if (!empty($date)) {
      list($year, $month, $day) = explode('-', $date);
    }
    // @todo: This is basically a fatal error.
    else {
      $year = date('Y');
      $month = date('m');
      $day = date('d');
    }
    $url_alias = "/archive/front/$year/$month/$day";
    try {
      $request = $client->get($url->toString());
      $status = $request->getStatusCode();
      $raw_html = $request->getBody()->getContents();
      $node = $this->loadNodeFromAlias($url_alias);
      // If we already have this front page archived, we want to create a new revision.
      if (!empty($node)) {
        $new_node = FALSE;
        $node->setNewRevision(TRUE);
        $node->setRevisionUserId($this->requestUID);
        $node->revision_log = t('Draft-to-live is updating an existing front page archive.');
      }
      // Otherwise, create a new node.
      else {
        $new_node = TRUE;
        $node = Node::create(
          [
            'type' => 'static_page',
            'title' => "SocialistWorker raw front page from $year-$month-$day",
            'status' => 1,
            'uid' => $this->requestUID,
            'path' => $url_alias,
          ]
        );
      }
      $node->set('field_static_body', $raw_html);
      if (!empty($date)) {
        $node->set('field_archive_date', $date);
      }
      $node->save();
      // @todo Modify the body based on the actual NID + path alias.
      // @todo Harvest + save CSS+JS?
      if ($verbose) {
        $placeholders = [
          '@nid' => $node->id(),
          ':canonical_url' => $node->toUrl('canonical')->toString(),
          ':edit_url' => $node->toUrl('edit-form')->toString(),
        ];
        if ($new_node) {
          drupal_set_message(t('Created a new <a href=":canonical_url">static page</a> (nid: @nid, <a href=":edit_url">edit</a>) to archive the current front page.', $placeholders));
        }
        else {
          drupal_set_message(t('Updated the existing <a href=":canonical_url">static page</a> (nid: @nid, <a href=":edit_url">edit</a>) to archive the current front page.', $placeholders));
        }
      }
    }
    catch (RequestException $e) {
      // @todo Deal with errors.
    }
  }

  /**
   * Clone the paragraphs from the draft page and put them into the live page.
   *
   * @param boolean $verbose
   *   Optional flag to control printing verbose messages to the screen.
   */
  protected function cloneDraftToLive($verbose = FALSE) {
    $paragraphs = $this->draftFrontPageNode->get('field_slices')->referencedEntities();
    $clones = [];
    foreach ($paragraphs as $paragraph) {
      $clone = $paragraph->createDuplicate();
      $clone->save();
      $clones[] = $clone;
    }
    $this->liveFrontPageNode->setNewRevision(TRUE);
    $this->liveFrontPageNode->set('field_slices', $clones);
    $this->liveFrontPageNode->save();
    if ($verbose) {
      $placeholders = [
        '@target' => $this->targetDraft,
        ':draft_url' => $this->draftFrontPageNode->toUrl('canonical')->toString(),
        ':live_url' => $this->liveFrontPageNode->toUrl('canonical')->toString(),
      ];
      drupal_set_message(t('Replaced slices from <a href=":draft_url">@target</a> into the <a href=":live_url">live front page</a>.', $placeholders));
    }
  }

}
