<?php

namespace Drupal\sw\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\Entity\User;

/**
 * Shared code for saving state (both site-wide and temporary).
 *
 * This is used by all of the SW draft-to-live forms (main and confirm).
 */
trait AdminFrontPageStateTrait {

  /**
   * The site-wide state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $siteState;

  /**
   * The keys we use in the site-wide state.
   *
   * @var array
   */
  protected $siteStateKeys;

  /**
   * User private temporary storage factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory;
   */
  protected $tempStoreFactory;

  /**
   * The tempstore object associated with draft-to-live.
   *
   * @var \Drupal\core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The site-wide state storage object.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   */
  public function __construct(
    StateInterface $state,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->siteState = $state;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->siteStateKeys = [
      'sw_front_page_target_draft',
      'sw_front_page_request_uid',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Initialize the draft-to-live tempstore object.
   */
  protected function getTempStore() {
    if (!isset($this->tempStore)) {
      $this->tempStore = $this->tempStoreFactory->get('sw_draft_to_live');
    }
    return $this->tempStore;
  }

  /**
   * Gets the current user tempstore data.
   */
  protected function getTempStoreData() {
    return $this->getTempStore()->get($this->currentUser()->id());
  }

  /**
   * Sets the current user tempstore data.
   *
   * @param array $data
   *   The array of key/value pairs to save into per-user temporary storage.
   */
  protected function setTempStoreData(array $data) {
    return $this->getTempStore()->set($this->currentUser()->id(), $data);
  }

  /**
   * Deletes the current user tempstore data.
   */
  protected function deleteTempStoreData() {
    return $this->getTempStore()->delete($this->currentUser()->id());
  }

  /**
   * Set site-wide state related to draft-to-live.
   *
   * @param array $data
   *   Array of key/value pairs to save in the site-wide state.
   */
  protected function setSiteState(array $data) {
    // @todo: Throw an error if the keys aren't in $this->siteStateKeys?
    return $this->siteState->setMultiple($data);
  }

  /**
   * Get the site-wide state related to draft-to-live.
   *
   * @param string $key
   *   An optional key to retrieve. If NULL, all state values are returned.
   *
   * @return array
   *   Array of key/value pairs saved in the site-wide state.
   */
  protected function getSiteState($key = NULL) {
    return !empty($key) ? $this->siteState->get($key) :
      $this->siteState->getMultiple($this->siteStateKeys);
  }

  /**
   * Get all site-wide state related to draft-to-live.
   */
  protected function deleteSiteState() {
    return $this->siteState->deleteMultiple($this->siteStateKeys);
  }

}
