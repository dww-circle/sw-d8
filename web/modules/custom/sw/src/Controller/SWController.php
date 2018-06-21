<?php

namespace Drupal\sw\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\forward\ForwardFormBuilder;
use Drupal\sw\EntityPathAliasTrait;
use Drupal\sw\StoryWordExporter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the SW module.
 */
class SWController extends ControllerBase {

  use EntityPathAliasTrait;

  /**
   * The forward form builder service.
   *
   * @var \Drupal\forward\Form\ForwardFormBuilder
   */
  protected $forwardFormBuilder;

  /**
   * Constructs a ForwardController object.
   *
   * @param \Drupal\forward\Form\ForwardFormBuilder $form_builder
   *   The forward form builder service.
   */
  public function __construct(ForwardFormBuilder $forward_form_builder) {
    $this->forwardFormBuilder = $forward_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('forward.form_builder')
    );
  }

  /**
   * Returns the forward form for a given story.
   *
   * @return array
   *   Render array for email-this-story form.
   */
  public function emailStoryPage($year, $month, $day, $title_alias) {
    $path_alias = "/$year/$month/$day/$title_alias";
    $node = $this->loadNodeFromAlias($path_alias);
    if (empty($node)) {
      // If we're on a broken link and can't load a story at that alias, bail.
      throw new NotFoundHttpException();
    }
    // Force the "link" interface so the Forward form page doesn't build inside a fieldset.
    $settings = $this->config('forward.settings')->get();
    $settings['forward_interface_type'] = 'link';
    return $this->forwardFormBuilder->buildForwardEntityForm($node, $settings);
  }

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
   * Builds the /section landing page.
   *
   * @return array
   *   Render array for the /section landing page.
   */
  public function sectionPage() {
    return $this->menuLandingPage('main', 'sw.section');
  }

  /**
   * Builds the /topic landing page.
   *
   * @return array
   *   Render array for the /topic landing page.
   */
  public function topicPage() {
    return $this->menuLandingPage('main', 'sw.topic');
  }

  /**
   * Builds the a landing page for a parent menu item.
   *
   * Loads any children menu items under the given menu link and renders them
   * all as a simple list, ordered by menu item weights.
   *
   * @param string $menu_name
   *   The machine name of the menu to harvest menu links from.
   * @param string $menu_item_route
   *   The route name of the parent menu item to find children items of.
   * @return array
   *   Render array for the requested landing page.
   */
  protected function menuLandingPage($menu_name, $menu_item_route) {
    $build = [];
    $menu_link_service = \Drupal::getContainer()->get('plugin.manager.menu.link');
    $menu_links = $menu_link_service->loadLinksByRoute($menu_item_route, [], $menu_name);
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

  /**
   * Controller callback for the word-export tab.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The fully loaded node entity to generate a word export tab for.
   * @return array
   *   The render array for the word export tab.
   *
   * @see \Drupal\sw\StoryWordExporter
   */
  public function wordExportPage(NodeInterface $node) {
    if ($node->bundle() != 'story') {
      return [];
    }
    $word_exporter = new StoryWordExporter($node);
    return $word_exporter->build();
  }

  /**
   * Access callback to ensure we're dealing with a story node.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function isStoryNode() {
    $bundle = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $bundle = $node->bundle();
    }
    return AccessResult::allowedIf($bundle == 'story');
  }

}
