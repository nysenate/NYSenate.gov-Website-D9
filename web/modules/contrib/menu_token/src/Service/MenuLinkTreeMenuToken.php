<?php

namespace Drupal\menu_token\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Template\Attribute;

/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class MenuLinkTreeMenuToken extends MenuLinkTree {

  /**
   * {@inheritdoc}
   */
  public function build(array $tree) {

    $tree_access_cacheability = new CacheableMetadata();
    $tree_link_cacheability = new CacheableMetadata();
    $items = $this->buildItems($tree, $tree_access_cacheability, $tree_link_cacheability);

    $build = [];

    // Apply the tree-wide gathered access cacheability metadata and link
    // cacheability metadata to the render array. This ensures that the
    // rendered menu is varied by the cache contexts that the access results
    // and (dynamic) links depended upon, and invalidated by the cache tags
    // that may change the values of the access results and links.
    $tree_cacheability = $tree_access_cacheability->merge($tree_link_cacheability);
    $tree_cacheability->applyTo($build);

    if ($items) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $item = end($items);
      $link = $item['original_link'];
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'menu__' . strtr($menu_name, '-', '_');
      $build['#menu_name'] = $menu_name;
      $build['#items'] = $items;
      // Set cache tag.
      $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
      $build['#cache']['contexts'] = $tree_cacheability->getCacheContexts();
    }

    return $build;
  }

  /**
   * Builds the #items property for a menu tree's renderable array.
   *
   * Helper function for ::build().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A data structure representing the tree, as returned from
   *   MenuLinkTreeInterface::load().
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_access_cacheability
   *   Internal use only. The aggregated cacheability metadata for the access
   *   results across the entire tree. Used when rendering the root level.
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_link_cacheability
   *   Internal use only. The aggregated cacheability metadata for the menu
   *   links across the entire tree. Used when rendering the root level.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   *
   * @throws \DomainException
   */
  protected function buildItems(array $tree, CacheableMetadata &$tree_access_cacheability, CacheableMetadata &$tree_link_cacheability) {
    $items = [];
    $current_path = \Drupal::request()->getRequestUri();

    foreach ($tree as $data) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }

      if ($data->access !== NULL && !$data->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Gather the access cacheability of every item in the menu link tree,
      // including inaccessible items. This allows us to render cache the menu
      // tree, yet still automatically vary the rendered menu by the same cache
      // contexts that the access results vary by.
      // However, if $data->access is not an AccessResultInterface object, this
      // will still render the menu link, because this method does not want to
      // require access checking to be able to render a menu tree.
      if ($data->access instanceof AccessResultInterface) {
        $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($data->access));
      }

      // Gather the cacheability of every item in the menu link tree. Some links
      // may be dynamic: they may have a dynamic text (e.g. a "Hi, <user>" link
      // text, which would vary by 'user' cache context), or a dynamic route
      // name or route parameters.
      $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($data->link));
      $def = $link->getPluginDefinition();

      if (!empty($def["options"]["bubleble_metadata"])) {

        $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($def["options"]["bubleble_metadata"]));
      }
      // Only render accessible links.
      if ($data->access instanceof AccessResultInterface && !$data->access->isAllowed()) {
        continue;
      }
      $element = [];

      // Set a variable for the <li> tag. Only set 'expanded' to true if the
      // link also has visible children within the current tree.
      $element['is_expanded'] = FALSE;
      $element['is_collapsed'] = FALSE;
      if ($data->hasChildren && !empty($data->subtree)) {
        $element['is_expanded'] = TRUE;
      }
      elseif ($data->hasChildren) {
        $element['is_collapsed'] = TRUE;
      }
      // Set a helper variable to indicate whether the link is in the active
      // trail.
      $element['in_active_trail'] = FALSE;
      if ($data->inActiveTrail) {
        $element['in_active_trail'] = TRUE;
      }

      // Note: links are rendered in the menu.html.twig template; and they
      // automatically bubble their associated cacheability metadata.
      $element['attributes'] = new Attribute();
      $element['title'] = $link->getTitle();
      $element['url'] = $link->getUrlObject();
      $element['url']->setOption('set_active_class', TRUE);
      $element['below'] = $data->subtree ? $this->buildItems($data->subtree, $tree_access_cacheability, $tree_link_cacheability) : [];
      if (isset($data->options)) {
        $element['url']->setOptions(NestedArray::mergeDeep($element['url']->getOptions(), $data->options));
      }
      if ($element['url']->toString() === $current_path) {
        $attributes = $element['url']->getOption('attributes') ?? [];
        $element['url']->setOption('attributes', NestedArray::mergeDeep($attributes, ['class' => ['is-active']]));
      }

      $element['original_link'] = $link;
      $items[$link->getPluginId()] = $element;
    }

    return $items;
  }

}
