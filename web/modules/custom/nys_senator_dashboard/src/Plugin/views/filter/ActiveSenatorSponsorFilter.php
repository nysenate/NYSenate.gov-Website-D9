<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\join\Standard;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;

/**
 * Provides an exposed filter for filtering bills sponsored by active senator.
 *
 * @ViewsFilter("nys_senator_dashboard_active_senator_sponsor_filter")
 */
class ActiveSenatorSponsorFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Managed Senators Handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected $managedSenatorsHandler;

  /**
   * Constructs the ActiveSenatorSponsorFilter plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managedSenatorsHandler
   *   The managed senators handler service.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, ManagedSenatorsHandler $managedSenatorsHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->managedSenatorsHandler = $managedSenatorsHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nys_senator_dashboard.managed_senators_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $senator = $this->managedSenatorsHandler->ensureAndGetActiveSenator(FALSE);
    if (empty($senator)) {
      return;
    }

    $sponsor_fields = [
      'field_ol_sponsor' => 'Sponsor',
      'field_ol_co_sponsors' => 'Co-Sponsor',
    ];
    foreach ($sponsor_fields as $field => $label) {
      $options["{$senator->id()}__$field"] = "{$senator->label()} - $label";
    }

    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => array_key_first($options),
      '#empty_option' => $this->t('- All senators -'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->value) || !isset($this->value[0])) {
      return;
    }

    $value_parts = explode('__', $this->value[0]);
    if (count($value_parts) < 2) {
      return;
    }

    $senator_tid = $value_parts[0];
    $sponsor_field = $value_parts[1];

    $join = new Standard(
      [
        'type' => 'LEFT',
        'table' => "node__{$sponsor_field}",
        'field' => 'entity_id',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
      ],
      $this->getPluginId(),
      $this->getPluginDefinition(),
    );

    if ($this->query instanceof Sql) {
      $relationship_alias = $this->query->addRelationship($sponsor_field, $join, 'node');
      if ($relationship_alias) {
        $this->query->addWhere(0, "$relationship_alias.$sponsor_field" . '_target_id', $senator_tid, '=');
      }
    }
  }

}
