<?php

namespace Drupal\sw;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;

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
   * A nested array of entities referenced by a given draft front page.
   *
   * Keys are string entity types, values are arrays of integer entity IDs.
   *
   * @var array
   */
  protected $referencedEntities;

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
    $this->referencedEntities = [];
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
    $current_time = \Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME');

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
      $year = format_date($current_time, 'custom', 'Y');
      $month = format_date($current_time, 'custom', 'm');
      $day = format_date($current_time, 'custom', 'd');
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
        $node->setRevisionLogMessage(t('Draft-to-live updating an existing archive.'));
      }
      // Otherwise, create a new node.
      else {
        $new_node = TRUE;
        $node = Node::create(
          [
            'type' => 'static_page',
            'title' => "$year-$month-$day front page raw HTML",
            'status' => 1,
            'uid' => $this->requestUID,
            'path' => $url_alias,
            'revision_log' => t('Draft-to-live creating initial archive.'),
          ]
        );
      }
      $node->set('field_static_body', $raw_html);
      $node->setNewRevision(TRUE);
      $node->setRevisionCreationTime($current_time);
      $node->setRevisionUserId($this->requestUID);
      if (!empty($date)) {
        $node->set('field_archive_date', $date);
      }
      $node->save();
      // @todo Modify the body based on the actual NID + path alias.
      // @todo Harvest + save CSS+JS?
      if ($verbose) {
        $placeholders = [
          '@nid' => $node->id(),
          ':view_url' => $node->toUrl()->toString(),
          ':edit_url' => $node->toUrl('edit-form')->toString(),
        ];
        if ($new_node) {
          drupal_set_message(t('Created a new <a href=":view_url">static page</a> (nid: @nid, <a href=":edit_url">edit</a>) to archive the current front page.', $placeholders));
        }
        else {
          drupal_set_message(t('Updated the existing <a href=":view_url">static page</a> (nid: @nid, <a href=":edit_url">edit</a>) to archive the current front page.', $placeholders));
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
    $draft_slices = $this->draftFrontPageNode->get('field_slices')->referencedEntities();
    $clones = [];
    foreach ($draft_slices as $slice) {
      $this->findAllReferences($slice);
      $clone = $slice->createDuplicate();
      $clone->save();
      $clones[] = $clone;
    }
    $this->publishAllReferences($verbose);
    $this->liveFrontPageNode->setNewRevision(FALSE);
    $live_slices = $this->liveFrontPageNode->get('field_slices')->referencedEntities();
    $this->liveFrontPageNode->set('field_slices', $clones);
    $this->liveFrontPageNode->save();
    foreach ($live_slices as $slice) {
      $slice->delete();
    }
    if ($verbose) {
      $placeholders = [
        '@target' => $this->targetDraft,
        ':draft_url' => $this->draftFrontPageNode->toUrl()->toString(),
        ':live_url' => $this->liveFrontPageNode->toUrl()->toString(),
      ];
      drupal_set_message(t('Replaced slices from <a href=":draft_url">@target</a> into the <a href=":live_url">live front page</a>.', $placeholders));
    }
  }

  /**
   * Find everything referenced by a given paragraph.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph entity to find references for publishing.
   *
   * @see publishAllReferences()
   */
  protected function findAllReferences($paragraph) {
    // Figure out what fields to look in, depending on the paragraph type.
    // @todo Maybe we should figure this out dynamically. We could iterate over
    // all the fields, look for entity references, figure out the entity type
    // from the field definition, etc.
    switch ($paragraph->getType()) {
      case 'today':
        $fields = [
          'node' => [
            'field_articles',
          ],
          'media' => [
            'field_ad_left',
            'field_ad_right',
          ],
        ];
        break;

      case 'weekend':
        $fields = [
          'node' => [
            'field_lead',
            'field_sub',
          ],
          'media' => [
            'field_ad_right',
          ],
        ];
        $nested_fields = [
          'field_nested_left',
          'field_nested_right',
        ];
        break;

      case 'nested':
        $fields['node'] = ['field_articles'];
        break;

      case 'full':
        $fields['media'] = ['field_ad'];
        break;

      case 'triptych':
        $fields['media'] = ['field_ads'];
        break;

    }

    // If there's nothing to search for in this paragraph, bail now.
    if (empty($fields)) {
      return;
    }

    // Iterate over all the fields we care about and find target IDs.
    foreach ($fields as $entity_type => $entity_fields) {
      foreach ($entity_fields as $field) {
        $values = $paragraph->get($field)->getValue();
        if (!empty($values)) {
          foreach ($values as $value) {
            $this->referencedEntities[$entity_type][] = $value['target_id'];
          }
        }
      }
    }
    // Nested fields point to other paragraphs. Recursion to the rescue!
    if (!empty($nested_fields)) {
      foreach ($nested_fields as $field) {
        $paragraphs = $paragraph->get($field)->referencedEntities();
        foreach ($paragraphs as $nested) {
          $this->findAllReferences($nested);
        }
      }
    }
  }

  /**
   * Publish everything referenced by all cloned paragraphs.
   *
   * @param boolean $verbose
   *   Optional flag to control printing verbose messages to the screen.
   *
   * @see findAllReferences()
   */
  protected function publishAllReferences($verbose = FALSE) {
    if (!empty($this->referencedEntities)) {
      foreach ($this->referencedEntities as $entity_type => $ids) {
        $entity_storage = \Drupal::entityManager()->getStorage($entity_type);
        $entities = $entity_storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          if (empty($entity->get('status')->value)) {
            $entity->set('status', 1);
            $entity->setNewRevision(FALSE);
            $entity->save();
            if ($verbose) {
              $placeholders = [
                '%label' => $entity->label(),
                ':url' => $entity->toUrl()->toString(),
                '@id' => $entity->id(),
              ];
              drupal_set_message(t('Published <a href=":url">%label</a> (id: @id).', $placeholders));
            }
          }
        }
      }
    }
  }

}
