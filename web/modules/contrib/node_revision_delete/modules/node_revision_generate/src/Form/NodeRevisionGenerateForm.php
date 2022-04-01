<?php

namespace Drupal\node_revision_generate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_revision_delete\Utility\Donation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node_revision_generate\NodeRevisionGenerateInterface;

/**
 * Class NodeRevisionGenerate.
 */
class NodeRevisionGenerateForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node revision generate interface.
   *
   * @var \Drupal\node_revision_generate\NodeRevisionGenerateInterface
   */
  protected $nodeRevisionGenerate;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\node_revision_generate\NodeRevisionGenerateInterface $node_revision_generate
   *   The node revision generate.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    NodeRevisionGenerateInterface $node_revision_generate
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeRevisionGenerate = $node_revision_generate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('node_revision_generate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revision_generate_generate_revisions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all Content types.
    $content_types = [];
    $form['bundles'] = [];

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $type) {
      $content_type_machine_name = $type->id();
      // If the content type don't have nodes should be disabled.
      if (!$this->nodeRevisionGenerate->existsNodesContentType($content_type_machine_name)) {
        $form['bundles'][$content_type_machine_name]['#disabled'] = TRUE;
        $content_types[$content_type_machine_name] = $this->t('@content_type. There are no nodes.', ['@content_type' => $type->label()]);
      }
      else {
        $content_types[$content_type_machine_name] = $type->label();
      }
    }

    // Sort the content types by content type name.
    asort($content_types);

    $form['bundles'] += [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#options' => $content_types,
      '#required' => TRUE,
    ];

    $form['revisions_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Revisions number'),
      '#min' => 1,
      '#default_value' => 1,
      '#description' => $this->t('The maximum number of revisions that will be created for each node of the selected content types.'),
      '#required' => TRUE,
    ];

    $form['age'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Revisions age'),
      '#description' => $this->t('The age between each generated revision.'),
      '#required' => TRUE,
    ];

    $form['age']['number'] = [
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => 1,
      '#required' => TRUE,
    ];

    $time_options = [
      '86400' => $this->t('Day'),
      '604800' => $this->t('Week'),
      '2592000' => $this->t('Month'),
    ];

    $form['age']['time'] = [
      '#type' => 'select',
      '#options' => $time_options,
    ];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('The first revision will be generated starting from the created date of the last node revision and the last one will not have a date in the future. So, depending on this maybe we will not generate the number of revisions you expect.'),
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate revisions'),
      '#button_type' => 'primary',
    ];

    // Adding donation text.
    $form['#prefix'] = Donation::getDonationText();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get selected content types.
    $bundles = array_filter($form_state->getValue('bundles'));

    // Get form values.
    $revisions_number = $form_state->getValue('revisions_number');
    $interval_number  = $form_state->getValue('number');
    $interval_time    = $form_state->getValue('time');

    // Get interval to generate revisions.
    $revisions_age = $interval_number * $interval_time;

    // Get the available nodes to generate revisions.
    $nodes_for_revisions = $this->nodeRevisionGenerate->getAvailableNodesForRevisions($bundles, $revisions_age);

    // Check if there is nodes to generate revisions.
    if ($nodes_for_revisions) {
      // Setting the batch.
      batch_set($this->nodeRevisionGenerate->getRevisionCreationBatch($nodes_for_revisions, $revisions_number, $revisions_age));
    }
    else {
      $this->messenger()->addWarning($this->t('There are not more available nodes to generate revisions of the selected content types and specified options.'));
    }
  }

}
