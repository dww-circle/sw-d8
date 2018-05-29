<?php

namespace Drupal\sw\EventSubscriber;

use Drupal\Core\Session\AccountProxy;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SWSubscriber.
 *
 * @package Drupal\sw\EventSubscriber
 */
class SWSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructor.
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {

    // This needs to run before RouterListener::onKernelRequest(), which has a
    // priority of 32. Otherwise, that aborts the request if no matching route
    // is found. However, 33 is what the Redirect module uses, and we want
    // that to run first (so specific redirects to Drupal nodes (where
    // possible) take precendence over this catch-all). So, we use the same
    // priority, but set sw.module to weight 1. That lets the order for
    // listeners to this event be redirect, sw and finally core.
    $events[KernelEvents::REQUEST][] = ['onRequestCheckEntityAccess', 28];
    $events[KernelEvents::REQUEST][] = ['onRequestEarlyRedirect', 33];
    return $events;
  }

  /**
   * Manipulates the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onRequestEarlyRedirect(GetResponseEvent $event) {
    global $base_path;
    $request_uri = $event->getRequest()->getRequestUri();
    $matches = [];
    // If it's *.shtml, redirect to the corresponding .php (for Pantheon).
    if (preg_match('#(.+)\.shtml$#', $request_uri, $matches)) {
      $response = new RedirectResponse($matches[1] . '.php', 301);
    }
    // If the URL is only the base_path and an integer, this must be from
    // socwrk.org and we should redirect to the corresponding node.
    elseif (preg_match('#^' . $base_path . '(\d+)$#', $request_uri, $matches)) {
      $response = new RedirectResponse($base_path . 'node/' . $matches[1], 301);
    }
    if (isset($response)) {
      $event->setResponse($response);
    }
  }

  /**
   * Checks for access to entity canonical route pages.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onRequestCheckEntityAccess(GetResponseEvent $event) {
    $request = $event->getRequest();
    // Don't process events with HTTP exceptions.
    if ($request->get('exception') != NULL) {
      return;
    }
    if ($route = $request->get('_route')) {
      // Only continue if the request route is the an entity canonical.
      $allow = TRUE;
      $matches = [];
      if (preg_match('/^entity\.(.+)\.canonical$/', $route, $matches)) {
        $entity = $event->getRequest()->get($matches[1]);
        $bundle = $entity->bundle();
        switch ($entity->getEntityTypeId()) {
          case 'node':
            if ($bundle == 'insert_box') {
              $allow = FALSE;
            }
            break;

          case 'media':
            if ($bundle != 'episode') {
              $allow = FALSE;
            }
            break;

          case 'taxonomy_term':
            $allow = FALSE;
            break;
        }
        if (!$allow && empty($this->currentUser->id())) {
          throw new AccessDeniedHttpException();
        }
      }
    }
  }

}
