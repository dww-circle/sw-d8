<?php

namespace Drupal\sw;

use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\node\Entity\Node;

trait EntityPathAliasTrait {
  /**
   * Load a node object with a given URL path alias.
   *
   * @param string $path_alias
   *   The URL path alias to search for.
   *
   * @return \Drupal\node\Entity\Node
   *   The fully loaded node object with the given alias, or NULL if not found.
   */
  public function loadNodeFromAlias($path_alias) {
    // @todo This should probably use dependency injection fun (somehow).
    $alias = \Drupal::service('path.alias_storage')->load(['alias' => $path_alias]);
    if (!empty($alias)) {
      $matches = [];
      if (preg_match('@/node/(\d+)@', $alias['source'], $matches)) {
        return Node::load($matches[1]);
      }
    }
  }
}
