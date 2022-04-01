<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkBase;

/**
 * Class EntityMenuLinkContentUrlGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "entity_menu_link_content",
 *   label = @Translation("Menu link URL generator"),
 *   description = @Translation("Generates menu link URLs by overriding the 'entity' URL generator."),
 *   settings = {
 *     "overrides_entity_type" = "menu_link_content",
 *   },
 * )
 *
 * @todo Find way of adding just a menu link item pointer to the queue instead of whole object.
 */
class EntityMenuLinkContentUrlGenerator extends EntityUrlGeneratorBase {

  /**
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * EntityMenuLinkContentUrlGenerator constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Logger $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entityHelper,
    MenuLinkTreeInterface $menu_link_tree
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $logger,
      $language_manager,
      $entity_type_manager,
      $entityHelper
    );
    $this->menuLinkTree = $menu_link_tree;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $bundle_settings = $this->generator
      ->setVariants($this->sitemapVariant)
      ->getBundleSettings();
    if (!empty($bundle_settings['menu_link_content'])) {
      foreach ($bundle_settings['menu_link_content'] as $bundle_name => $bundle_settings) {
        if (!empty($bundle_settings['index'])) {

          // Retrieve the expanded tree.
          $tree = $this->menuLinkTree->load($bundle_name, new MenuTreeParameters());
          $tree = $this->menuLinkTree->transform($tree, [
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
            ['callable' => 'menu.default_tree_manipulators:flatten'],
          ]);

          foreach ($tree as $i => $item) {
            $data_sets[] = $item->link;
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * @inheritdoc
   *
   * @todo Find a way to be able to check if a menu link still exists. This is difficult as we don't operate on MenuLinkContent entities, but on Link entities directly (as some menu links are not MenuLinkContent entities).
   */
  protected function processDataSet($data_set) {

    /** @var  MenuLinkBase $data_set */
    if (!$data_set->isEnabled()) {
      return FALSE;
    }

    $url_object = $data_set->getUrlObject()->setAbsolute();

    // Do not include external paths.
    if ($url_object->isExternal()) {
      return FALSE;
    }

    // If not a menu_link_content link, use bundle settings.
    $meta_data = $data_set->getMetaData();
    if (empty($meta_data['entity_id'])) {
      $entity_settings = $this->generator
        ->setVariants($this->sitemapVariant)
        ->getBundleSettings('menu_link_content', $data_set->getMenuName());
    }

    // If menu link is of entity type menu_link_content, take under account its entity override.
    else {
      $entity_settings = $this->generator
        ->setVariants($this->sitemapVariant)
        ->getEntityInstanceSettings('menu_link_content', $meta_data['entity_id']);

      if (empty($entity_settings['index'])) {
        return FALSE;
      }
    }

    if ($url_object->isRouted()) {

      // Do not include paths that have no URL.
      if (in_array($url_object->getRouteName(), ['<nolink>', '<none>'])) {
        return FALSE;
      }

      $path = $url_object->getInternalPath();
    }
    // There can be internal paths that are not rooted, like 'base:/path'.
    else { // Handle base scheme.
      if (strpos($uri = $url_object->toUriString(), 'base:/') === 0 ) {
        $path = $uri[6] === '/' ? substr($uri, 7) : substr($uri, 6);
      }
      else { // Handle unforeseen schemes.
        $path = $uri;
      }
    }

    $entity = $this->entityHelper->getEntityFromUrlObject($url_object);

    $path_data = [
      'url' => $url_object,
      'lastmod' => !empty($entity) && method_exists($entity, 'getChangedTime')
        ? date('c', $entity->getChangedTime())
        : NULL,
      'priority' => isset($entity_settings['priority']) ? $entity_settings['priority'] : NULL,
      'changefreq' => !empty($entity_settings['changefreq']) ? $entity_settings['changefreq'] : NULL,
      'images' => !empty($entity_settings['include_images']) && !empty($entity)
        ? $this->getEntityImageData($entity)
        : [],

      // Additional info useful in hooks.
      'meta' => [
        'path' => $path,
      ]
    ];
    if (!empty($entity)) {
      $path_data['meta']['entity_info'] = [
        'entity_type' => $entity->getEntityTypeId(),
        'id' => $entity->id(),
      ];
    }

    return $path_data;
  }
}
