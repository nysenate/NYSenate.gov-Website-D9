<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Service\NysFeedPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * NYS Feeds plugin to list all available feeds.
 *
 * @NysFeed(
 *   id = "feed_list",
 *   label = @Translation("Feed List"),
 *   description = @Translation("Returns all known feeds"),
 * )
 */
class FeedList extends NysFeedPluginBase {

  /**
   * The NYS Feed Plugin Manager service.
   *
   * @var \Drupal\nys_feeds\Service\NysFeedPluginManager
   */
  private NysFeedPluginManager $feedManager;

  /**
   * Constructor.
   */
  public function __construct(NysFeedPluginManager $feedManager, array $definition, array $config = []) {
    parent::__construct($definition, $config);
    $this->feedManager = $feedManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($container->get('nys_feeds.feed_manager'), $plugin_definition, $configuration);
  }

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    $ret = [];
    foreach ($this->feedManager->getDefinitions() as $definition) {
      $ret[$definition['id']] = [
        'name' => $definition['label'],
        'description' => $definition['description'],
      ];
    }
    return $ret;
  }

}
