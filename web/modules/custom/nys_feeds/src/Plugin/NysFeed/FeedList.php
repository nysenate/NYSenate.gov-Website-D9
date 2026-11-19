<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Service\NysFeedPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * NYS Feeds plugin to list all available feeds.
 */
#[NysFeed(
  label: new TranslatableMarkup("Feed list"),
  description: new TranslatableMarkup("Returns all known feeds."),
  id: "feed_list",
)]
class FeedList extends NysFeedPluginBase {

  /**
   * {@inheritDoc}
   *
   * Adds the NysFeedPluginManager service.
   */
  public function __construct(
    protected NysFeedPluginManager $feedManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    parent::__construct($entityTypeManager, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('nys_feeds.feed_manager'),
      $container->get('entity_type.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    $ret = [];
    foreach ($this->feedManager->getDefinitions() as $definition) {
      if (!$definition['private']) {
        $ret[$definition['id']] = [
          'name' => $definition['label'],
          'description' => $definition['description'],
          'parameters' => implode(',', array_keys($definition['params'])),
        ];
      }
    }
    return $ret;
  }

}
