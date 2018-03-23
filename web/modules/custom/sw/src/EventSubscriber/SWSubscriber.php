<?php

namespace Drupal\sw\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SWSubscriber.
 *
 * @package Drupal\sw\EventSubscriber
 */
class SWSubscriber implements EventSubscriberInterface {

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
    $events[KernelEvents::REQUEST][] = ['onRequestRedirectSHTML', 33];
    return $events;
  }

  /**
   * Manipulates the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onRequestRedirectSHTML(GetResponseEvent $event) {
    $response = $event->getRequest();
    $matches = [];
    if (preg_match('#(.+)\.shtml$#', $response->getRequestUri(), $matches)) {
      $response = new RedirectResponse($matches[1] . '.php', 301);
      $event->setResponse($response);
    }
  }

}
