<?php

namespace Drupal\nys_senators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for senator dashboard overview stat blocks.
 */
abstract class OverviewStatBase implements OverviewStatInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition = [];

  /**
   * The HTML or plain text content.
   *
   * @var string
   */
  protected string $content = '';

  /**
   * Constructor.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getDefinition(): array {
    return $this->definition;
  }

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): string {
    if (!$this->content) {
      $this->content = $this->buildContent($senator);
    }
    return $this->content;
  }

  /**
   * Override to build the HTML/plain-text content.
   */
  abstract protected function buildContent(TermInterface $senator): string;

  /**
   * Indicates if this stat block will be a link.
   */
  public function isLink(): bool {
    return (bool) ($this->definition['url'] ?? FALSE);
  }

}
