<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flag\FlagLinkBuilder;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views field to generate flag link using contextual filter value.
 *
 * @ViewsField("nys_senator_dashboard_contextual_filter_flag_link")
 */
class ContextualFilterFlagLink extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The flag.link_builder service.
   *
   * @var \Drupal\flag\FlagLinkBuilder
   */
  protected $linkBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ContextualFilterFlagLink object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flag\FlagLinkBuilder $link_builder
   *   The flag.link_builder service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlagLinkBuilder $link_builder, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkBuilder = $link_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag.link_builder'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $issue_id = $this->view?->argument['entity_id']?->argument;
    if (!empty($issue_id)) {
      $link = $this->linkBuilder->build('taxonomy_term', $issue_id, 'follow_issue', 'default');
      return $this->renderer->render($link);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally do nothing here as field data comes from contextual filter.
  }

}
