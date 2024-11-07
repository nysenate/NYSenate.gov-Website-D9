<?php

namespace Drupal\nys_bills\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\NodeInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to choose between a bill's amendments.
 */
#[ViewsFilter("bill_amendments_filter")]
class BillAmendmentsFilter extends FilterPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public EntityTypeManager $entityTypeManager;

  /**
   * Request stack service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  public CurrentRouteMatch $currentRouteMatch;

  /**
   * Constructs a YourSenaterFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current route service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state): void {
    try {
      $node_storage = $this->entityTypeManager->getStorage('node');
    }
    catch (\Exception) {
      $this->returnNoResults();
      return;
    }

    // Get current bill node and ensure it has required fields.
    $current_node = $this->currentRouteMatch->getParameter('node');
    if (
      empty($current_node)
      && !empty($form_state->getUserInput()['bill_amendment_filter'])
    ) {
      $current_node = $node_storage
        ->load($form_state->getUserInput()['bill_amendment_filter']);
    }
    if (
      !($current_node instanceof NodeInterface)
      || empty($current_node->field_ol_session->value)
      || empty($current_node->field_ol_base_print_no->value)
    ) {
      $this->returnNoResults();
      return;
    }

    // Build options to toggle between bill amendments.
    $options = [];
    $bill_amendment_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ol_session', $current_node->field_ol_session->value)
      ->condition('field_ol_base_print_no', $current_node->field_ol_base_print_no->value)
      ->execute();
    $amendment_bills = $node_storage->loadMultiple($bill_amendment_nids);
    foreach ($amendment_bills as $bill) {
      $version = $bill->field_ol_version->value;
      $is_active = $bill->field_ol_is_active_version->value;
      $options[$bill->id()] = ($version ?? 'Original') . ($is_active ? ' (Active)' : '');
    }

    $form['bill_amendment_filter'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $current_node->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input): bool {
    if (isset($input['bill_amendment_filter'])) {
      $this->value = $input['bill_amendment_filter'];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds a condition to the query that ensures the view returns no results.
   */
  public function returnNoResults(): void {
    $this->query->addWhere(0, 'node_field_data.nid', 0, '=');
  }

}
