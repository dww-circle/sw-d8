<?php

namespace Drupal\sw\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\sw\EntityPathAliasTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the SW module.
 */
class SWController extends ControllerBase {

  use EntityPathAliasTrait;

  /**
   * Returns Readers' Views contact form as a custom page.
   *
   * @return array
   *   Render array for the customized readers' views form for a given story.
   */
  public function respondPage($year, $month, $day, $title_alias) {
    $path_alias = "/$year/$month/$day/$title_alias";
    $node = $this->loadNodeFromAlias($path_alias);
    if (empty($node)) {
      // If we're on a broken link and can't load a story at that alias, bail.
      throw new NotFoundHttpException();
    }
    $message = $this->entityManager()
      ->getStorage('contact_message')
      ->create([
        'contact_form' => 'readers_views',
      ]);
    // Set this here so the value is already present when we call getForm().
    $message->set('field_reply_story', $node->id());
    $message->set('subject', $this->t('Response: @label', ['@label' => $node->label()]));
    $form = $this->entityFormBuilder()->getForm($message);
    // Now that we've got the right story loaded, hide the story and subject fields.
    $form['field_reply_story']['#access'] = FALSE;
    $form['subject']['#access'] = FALSE;
    $placeholders = [
      ':story_url' => $node->toUrl()->toString(),
      '%story_label' => $node->label(),
    ];
    $form['story_link'] = [
      '#markup' => t('Please send us your thoughts on <a href=":story_url">%story_label</a>.', $placeholders),
      '#weight' => -101,
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $form['section_link'] = [
      '#markup' => t('We will publish a selection of comments in the <a href=":readers_views_url">Readers’ Views</a> section of SocialistWorker.org.',
                     [':readers_views_url' => Url::fromUserInput('/section/readers’-views')->toString()]),
      '#weight' => -100,
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    // We can't set a custom submit handler here, since the entity form world
    // will clobber the values via EntityForm::actions() and friends when
    // building the form during the submit phase. We need to do it via
    // hook_form_alter() for it to survive Entity API.
    // @see sw_form_contact_message_readers_views_form_alter().
    return $form;
  }

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
