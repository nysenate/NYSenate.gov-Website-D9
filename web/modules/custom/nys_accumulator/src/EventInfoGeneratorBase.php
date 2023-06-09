<?php

namespace Drupal\nys_accumulator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for event info generator plugins.
 */
abstract class EventInfoGeneratorBase implements EventInfoGeneratorInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * The plugin id.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * Optional configuration.
   *
   * @var array
   */
  protected array $configuration;

  /**
   * Constructor.
   */
  public function __construct(array $definition, string $plugin_id, array $configuration = []) {
    $this->definition = $definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($plugin_definition, $plugin_id, $configuration);
  }

  /**
   * Validates the type of entity being used against the definition requirement.
   */
  protected function isValidSource(ContentEntityBase $source): bool {
    $type = $source->getEntityTypeId() . ':' . $source->bundle();
    return in_array($type, $this->definition['requires']);
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityBase $source): array {
    if (!$this->isValidSource($source)) {
      throw new \InvalidArgumentException("Source requirements not met for event info generator");
    }
    return $this->doBuild($source);
  }

  /**
   * Does the actual building.
   *
   * @throws \InvalidArgumentException
   */
  public function doBuild(ContentEntityBase $source): array {
    $ret = [];

    try {
      foreach ($this->definition['fields'] as $key => $val) {
        $ret[$key] = $source->{$val}->value;
      }

      if ($this->definition['content_url']) {
        $ret['content_url'] = $this->definition['content_url'] . '/' . $source->id();
      }

      $this->extraBuild($source, $ret);

      return $ret;
    }
    catch (\Throwable) {
      throw new \InvalidArgumentException(static::class . ' received a malformed source entity');
    }
  }

  /**
   * Can be overridden to populate extra info keys beyond the defined fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $source
   *   The source entity.
   * @param array $ret
   *   The proposed return array.
   */
  protected function extraBuild(ContentEntityBase $source, array &$ret): void {

  }

}
