<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flag\FlagLinkBuilder;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views field to generate flag link using contextual filter value.
 *
 * @ViewsField("contextual_filter_flag_link")
 */
class ContextualFilterFlagLink extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The flag.link_builder service.
   *
   * @var \Drupal\flag\FlagLinkBuilder
   */
  protected $linkBuilder;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlagLinkBuilder $link_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkBuilder = $link_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag.link_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $issue_id = $this->view?->argument['entity_id']?->argument;
    if (!empty($issue_id)) {
      $link = $this->linkBuilder->build('taxonomy_term', $issue_id, 'follow_issue');
    }
    return $link ?? ['#markup' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally do nothing here since we're only providing a link and not
    // querying against a real table column.
  }

}
