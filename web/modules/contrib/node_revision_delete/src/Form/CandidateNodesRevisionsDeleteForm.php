<?php

namespace Drupal\node_revision_delete\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a candidate node revision deletion confirmation form.
 */
class CandidateNodesRevisionsDeleteForm extends ConfirmFormBase {

  /**
   * The node type object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The node revision delete interface.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected NodeRevisionDeleteInterface $nodeRevisionDelete;

  /**
   * Constructor.
   *
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $node_revision_delete
   *   The node revision delete.
   */
  public function __construct(NodeRevisionDeleteInterface $node_revision_delete) {
    $this->nodeRevisionDelete = $node_revision_delete;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('node_revision_delete')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'node_revision_delete_candidates_nodes_revisions_delete';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string|null $node_type
   *   The node type machine name.
   * @param \Drupal\node\NodeInterface|null $node
   *   The node.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $node_type = NULL, ?NodeInterface $node = NULL): array {
    $this->node = $node;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the candidates revisions for the node "%node_title" ?', ['%node_title' => $this->node->getTitle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    $description = '<p>' . $this->t('This action will delete the candidate revisions for the "@node_title" content type.', ['@node_title' => $this->node->getTitle()]) . '</p>';
    $description .= '<p>' . parent::getDescription() . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('node_revision_delete.candidate_nodes', ['node_type' => $this->node->getType()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Getting the content type candidate revisions.
    $candidate_revisions = $this->nodeRevisionDelete->getCandidatesRevisionsByNids([$this->node->id()]);

    // Add the batch.
    batch_set($this->nodeRevisionDelete->getRevisionDeletionBatch($candidate_revisions, FALSE));

    // Redirecting.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
