<?php

namespace Drupal\config_split;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EntityViewBuilder for Config Split entities.
 */
class ConfigSplitEntityViewBuilder extends EntityViewBuilder {

  /**
   * The plugin manager for config filter plugins.
   *
   * @var \Drupal\config_filter\Plugin\ConfigFilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $handler = parent::createInstance($container, $entity_type);
    $handler->filterPluginManager = $container->get('plugin.manager.config_filter');
    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\config_split\Entity\ConfigSplitEntityInterface[] $entities */
    $build = [];

    foreach ($entities as $entity_id => $entity) {
      /** @var \Drupal\config_split\Plugin\ConfigFilter\SplitFilter $filter */
      $filter = $this->filterPluginManager->getFilterInstance('config_split:' . $entity->id());

      // @todo: make this prettier.
      $build[$entity_id] = [
        'complete' => [
          '#type' => 'container',
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $this->t('Complete Split Config'),
          ],
          'items' => [
            '#theme' => 'item_list',
            '#items' => $filter->getBlacklist(),
            '#list_type' => 'ul',
          ],
        ],
        'conditional' => [
          '#type' => 'container',
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $this->t('Conditional Split Config'),
          ],
          'items' => [
            '#theme' => 'item_list',
            '#items' => $filter->getGraylist(),
            '#list_type' => 'ul',
          ],
        ],
        '#cache' => [
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }

    return $build;
  }

}
