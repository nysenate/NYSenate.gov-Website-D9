<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\join\Standard;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;

/**
 * Provides an exposed filter for filtering bills sponsored by active senator.
 *
 * @ViewsFilter("active_senator_sponsor_filter")
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
    $options = [];
    $senator = $this->managedSenatorsHandler->getActiveSenator(FALSE);

    if (!empty($senator)) {
      $options = [
        $senator->id() => $senator->label(),
      ];
    }

    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => $this->t('All senators'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (
      !empty($this->value)
      && !(
        is_array($this->value)
        && array_filter($this->value) === []
      )
    ) {
      $sponsor_field = $this->options['sponsor_field'] ?? '';
      if (!empty($sponsor_field)) {
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
        $relationship_alias = $this->query->addRelationship($sponsor_field, $join, 'node');
        if ($relationship_alias) {
          $this->query->addWhere(0, "$relationship_alias.$sponsor_field" . '_target_id', $this->value, '=');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['sponsor_field'] = [
      'default' => '',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['sponsor_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sponsor field name'),
      '#description' => $this->t('Enter the bill sponsor field machine name.'),
      '#default_value' => $this->options['sponsor_field'] ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::submitOptionsForm($form, $form_state);
    $form_value = $form_state->getValue('options')['sponsor_field'] ?? '';
    $this->options['sponsor_field'] = $form_value;
  }

}
