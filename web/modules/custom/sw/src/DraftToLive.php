<?php

namespace Drupal\sw;

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
  }

  /**
   * Execute all the draft-to-live operations.
   *
   * @param boolean $verbose
   *   Optional flag to control if the operations should print verbose messages to the screen.
   */
  public function execute($verbose = FALSE) {
    $this->archiveCurrentFrontPage($verbose);
    //$this->cloneDraftFrontPage($verbose);
    //$this->publishClonedFrontPage($verbose);
    //$this->replaceFrontPage($verbose);
  }

  /**
   * Archive the current front page.
   *
   * @param boolean $verbose
   *   Optional flag to control printing verbose messages to the screen.
   */
  public function archiveCurrentFrontPage($verbose = FALSE) {
    $client = \Drupal::httpClient();
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    // @todo Load the actual front page entity, search through the slices for the active date.
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $url_alias = "/archive/front/$year/$month/$day";
    // @todo: Deal with over-writing an existing record, or use revisions, or something...
    try {
      $request = $client->get($url->toString());
      $status = $request->getStatusCode();
      $node = Node::create(
        [
          'type' => 'static_page',
          'title' => "SocialistWorker raw front page from $year-$month-$day",
          'field_static_body' => [
            'value' => $request->getBody()->getContents(),
          ],
          'status' => 1,
          'uid' => $this->requestUID,
          'path' => $url_alias,
        ]
      );
      $node->save();
      // @todo Modify the body based on the actual NID + path alias.
      // @todo Harvest + save CSS+JS?
      if ($verbose) {
        $archive_url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
        $placeholders = [
          '@nid' => $node->id(),
          ':archive_url' => $archive_url->toString(),
        ];
        drupal_set_message(t('Created a <a href=":archive_url">static page</a> (nid: @nid) to archive the current front page.', $placeholders));
      }
    }
    catch (RequestException $e) {
      // @todo Deal with errors.
    }
  }

}
