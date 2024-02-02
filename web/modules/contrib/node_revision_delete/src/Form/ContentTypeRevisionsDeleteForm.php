<?php

namespace Drupal\node_revision_delete\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeTypeInterface;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content type revision deletion confirmation form.
 */
class ContentTypeRevisionsDeleteForm extends ConfirmFormBase {

  /**
   * The node type object.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $contentType;

  /**
   * The node revision delete.
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
    return 'node_revision_delete_content_type_revisions_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeTypeInterface $node_type = NULL): array {
    $this->contentType = $node_type;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the candidates revisions for the "%content_type" content type?', ['%content_type' => $this->contentType->label()]);
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
    $description = '<p>' . $this->t('This action will delete the candidate revisions for the "@content_type" content type.', ['@content_type' => $this->contentType->label()]) . '</p>';
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
    return new Url('node_revision_delete.admin_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Getting the content type candidate revisions.
    $candidate_revisions = $this->nodeRevisionDelete->getCandidatesRevisions($this->contentType->id());

    // Add the batch.
    batch_set($this->nodeRevisionDelete->getRevisionDeletionBatch($candidate_revisions, FALSE));

    // Redirecting.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
