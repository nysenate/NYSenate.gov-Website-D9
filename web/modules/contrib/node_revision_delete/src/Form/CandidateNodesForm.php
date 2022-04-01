<?php

namespace Drupal\node_revision_delete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node_revision_delete\Utility\Donation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Class CandidateNodesForm.
 *
 * @package Drupal\node_revision_delete\Form
 */
class CandidateNodesForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node revision delete interface.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected $nodeRevisionDelete;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $node_revision_delete
   *   The node revision delete.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    NodeRevisionDeleteInterface $node_revision_delete,
    DateFormatterInterface $date_formatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeRevisionDelete = $node_revision_delete;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('node_revision_delete'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revision_delete_candidates_nodes';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeTypeInterface $node_type = NULL) {
    // Table header.
    $header = [
      $this->t('Nid'),
      [
        'data' => $this->t('Title'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Author'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Status'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Updated'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Candidate revisions'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      $this->t('Operations'),
    ];
    // Table rows.
    $rows = [];

    // Getting the node type machine name.
    $node_type_machine_name = $node_type->id();

    // Getting the candidate nodes.
    $candidate_nodes = $this->nodeRevisionDelete->getCandidatesNodes($node_type_machine_name);
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($candidate_nodes);

    // Setup array to store candidate revisions keyed by node id.
    $candidate_revisions = [];

    /** @var \Drupal\node\Entity\Node $node */
    foreach ($nodes as $node) {
      $nid = $node->id();

      $route_parameters = [
        'node_type' => $node_type_machine_name,
        'node' => $nid,
      ];

      // Get node's candidate revisions for count display and form_state
      // storage.
      $node_candidate_revisions = $this->nodeRevisionDelete->getCandidatesRevisionsByNids([$nid]);
      $candidate_revisions[$nid] = $node_candidate_revisions;
      $node_revision_count = count($node_candidate_revisions);
      // Formatting the numbers.
      $node_revision_count = number_format($node_revision_count, 0, '.', '.');
      // Creating a link to the candidate revisions page.
      $candidate_revisions_link = Link::createFromRoute($node_revision_count, 'node_revision_delete.candidate_revisions_node', $route_parameters);

      $dropbutton = [
        '#type' => 'dropbutton',
        '#links' => [
          // Action to delete revisions.
          'delete_revisions' => [
            'title' => $this->t('Delete revisions'),
            'url' => Url::fromRoute('node_revision_delete.candidate_nodes_revisions_delete_confirm', $route_parameters),
          ],
        ],
      ];

      // Setting the row values.
      $rows[$nid] = [
        $nid,
        Link::fromTextAndUrl($node->getTitle(), $node->toUrl('canonical')),
        $node->getOwner()->getAccountName() ? Link::fromTextAndUrl($node->getOwner()->getAccountName(), $node->getOwner()->toUrl('canonical')) : $this->t('Anonymous (not verified)'),
        $node->isPublished() ? $this->t('Published') : $this->t('Not published'),
        $this->dateFormatter->format($node->getChangedTime(), 'short'),
        $candidate_revisions_link,
        [
          'data' => $dropbutton,
        ],
      ];
    }

    $content_type_url = $node_type->toUrl()->toString();
    $caption = $this->t('Candidates nodes for content type <a href=":url">%title</a>', [':url' => $content_type_url, '%title' => $node_type->label()]);

    $form['candidate_nodes'] = [
      '#type' => 'tableselect',
      '#caption' => $caption,
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('There are not candidates nodes with revisions to be deleted.'),
      '#sticky' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete revisions'),
      '#button_type' => 'primary',
    ];

    // Adding donation text.
    $form['#prefix'] = Donation::getDonationText();

    // Add all candidate revisions into form_state for use in
    // the submitForm() method.
    $form_state->set('candidate_revisions', $candidate_revisions);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get selected candidate nodes.
    $nids = array_filter($form_state->getValue('candidate_nodes'));

    if (count($nids)) {
      // Get selected node's candidate revisions from form_state to delete.
      $candidate_revisions = [];
      $candidate_revisions_by_nid = array_intersect_key($form_state->get('candidate_revisions'), $nids);
      foreach ($candidate_revisions_by_nid as $nid => $revisions) {
        $candidate_revisions = array_merge($candidate_revisions, $revisions);
      }

      // Add the batch.
      batch_set($this->nodeRevisionDelete->getRevisionDeletionBatch($candidate_revisions, FALSE));
    }
  }

}
