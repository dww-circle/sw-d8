<?php

namespace Drupal\sw\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the SW module.
 */
class SWController extends ControllerBase {

  /**
   * Returns the e-mail alerts signup form page.
   *
   * @return array
   *   Render array for the e-mail alert signup form.
   */
  public function subscribeEmailPage() {
    return [
      '#theme' => 'sw_mailerlite_subscribe_form',
      '#form_type' => 'page',
    ];
  }

  /**
   * Builds the /topic landing page.
   *
   * Loads any children menu items under /topic and renders them all as a simple
   * list, ordered by menu item weights.
   *
   * @return array
   *   Render array for the /topic landing page.
   */
  public function topicPage() {
    $build = [];
    $menu_name = 'main';
    $menu_link_service = \Drupal::getContainer()->get('plugin.manager.menu.link');
    $menu_links = $menu_link_service->loadLinksByRoute('sw.topic', [], $menu_name);
    if (!empty($menu_links)) {
      $root_menu_item = reset($menu_links);
      $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
      $menu_parameters->setMaxDepth(1);
      $menu_parameters->setRoot($root_menu_item->getPluginId());
      $menu_parameters->excludeRoot();
      $menu_tree_service = \Drupal::service('menu.link_tree');
      $tree = $menu_tree_service->load($menu_name, $menu_parameters);
      if (!empty($tree)) {
        $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $tree = $menu_tree_service->transform($tree, $manipulators);
        $build['menu_items'] = $menu_tree_service->build($tree);
      }
    }
    return $build;
  }

}
