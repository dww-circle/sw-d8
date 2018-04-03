<?php

namespace Drupal\sw_migrate\Plugin\migrate\process;

use Drupal\Core\Url;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\MenuLinkParent;
use Drupal\migrate\Row;

/**
 * This plugin figures out menu link parent plugin IDs.
 *
 * @MigrateProcessPlugin(
 *   id = "sw_menu_link_parent"
 * )
 */
class SWMenuLinkParent extends MenuLinkParent {
  /**
   * {@inheritdoc}
   *
   * Find the parent link GUID.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($menu_name, $parent_link_path) = $value;
    if (empty($parent_link_path)) {
      // Root level, we're done.
      return '';
    }
    $url = Url::fromUserInput($parent_link_path);
    if ($url->isRouted()) {
      $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
      if (count($links) == 1) {
        /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
        $link = reset($links);
        return $link->getPluginId();
      }
    }
    throw new MigrateSkipRowException("Couldn't find parent menu item: " . $parent_link_path);
  }

}
