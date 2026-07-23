<?php

namespace Drupal\nys_feeds;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for NYS Feeds plugins.
 */
abstract class NysFeedPluginBase extends PluginBase implements NysFeedPluginInterface {

  /**
   * The state monitor for the current request.
   *
   * @var \Drupal\nys_feeds\FeedState
   */
  protected FeedState $state;

  /**
   * {@inheritDoc}
   *
   * Adds Drupal's Entity Type Manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Initialize the state and URL parameters.
    $this->state = $configuration['state'] ?? throw new \InvalidArgumentException('NYS Feed plugin configuration must include a valid FeedState instance.');
    $this->initParams();
  }

  /**
   * {@inheritDoc}
   *
   *  Adds Drupal's Entity Type Manager service.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('entity_type.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * Gets an attribute parameter from the plugin definition.
   */
  public function getParameter(string $name): mixed {
    return $this->getParameters()[$name] ?? NULL;
  }

  /**
   * Wrapper method to retrieve all plugin parameters.
   */
  public function getParameters(): array {
    return $this->getPluginDefinition()['params'] ?? [];
  }

  /**
   * Initializes the configured parameters with the defined defaults. Chainable.
   */
  protected function initParams(): self {
    $this->state->params += array_filter($this->getParameters());

    // Allow classes to massage the configured parameters.
    return $this->resolveParams();
  }

  /**
   * Transforms configured parameters in ::$state.  Chainable.
   */
  protected function resolveParams(): self {
    return $this;
  }

  /**
   * Instantiates an entity query based on the plugin definition.
   *
   * Leverages the definition's entity_type and bundle properties.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getQuery(): QueryInterface {
    $type = $this->getPluginDefinition()['entity_type'];
    $bundle = $this->getPluginDefinition()['bundle'];
    $bundle_field = $this->entityTypeManager->getDefinition($type)
      ->getKey('bundle');

    return \Drupal::entityQuery($type)
      ->condition($bundle_field, $bundle, '=')
      ->accessCheck();
  }

  /**
   * Override in the plugin to create/fetch the relevant data.
   *
   * @return array
   *   Array of results.
   */
  protected function query(): array {
    return [];
  }

  /**
   * Transcribes a single entry into a JSON-ready array.
   */
  protected function transcribeEntry(mixed $data): mixed {
    return $data;
  }

  /**
   * Constructs the cache ID.
   */
  protected function cacheId(): string {
    return 'nys_feeds:' . $this->getPluginId();
  }

  /**
   * Builds the cache contexts for the individual plugin.
   */
  protected function buildContext(): array {
    // Try to get the "list" cache tag for the defined entity type.
    // If this fails, just skip it..?  Not sure this will be important.
    try {
      $list_tag = $this->entityTypeManager
        ->getStorage($this->getPluginDefinition()['entity_type'])
        ->getEntityType()
        ->getListCacheTags();
      $this->state->cache()->addCacheTags($list_tag);
    }
    catch (\Throwable) {
      // I guess we just don't get a list tag...
    }

    $pre_context = array_filter(
      array_keys($this->state->params),
      fn($k) => isset($this->state->params[$k])
    );
    $context = array_map(
      fn($v) => "url.query_args:$v",
      $pre_context
    );
    if (count($context)) {
      $this->state->cache()->addCacheContexts($context);
    }
    return $context;
  }

  /**
   * {@inheritDoc}
   *
   * Can optionally pass in a new FeedState to start fresh.
   */
  public function getFeed(): FeedState {

    // Compile the results.  Add each result as a cache dependency.
    $data = $this->query();
    foreach ($data as $key => $val) {
      $this->state->data[$key] = $this->transcribeEntry($val);
      if ($this->state->useCache()) {
        $this->state->cache()->addCacheableDependency($val);
      }
    }
    if (!count($data)) {
      $this->state->messages[] = "No data found";
    }

    $this->buildContext();

    return $this->state;
  }

}
