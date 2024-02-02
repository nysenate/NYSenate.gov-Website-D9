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
 * Provides a content type configuration deletion confirmation form.
 */
class ContentTypeConfigurationDeleteForm extends ConfirmFormBase {

  /**
   * The node type object.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $contentType;

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
    return 'node_revision_delete_content_type_configuration_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeTypeInterface $node_type = NULL): array {
    $this->contentType = $node_type;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the configuration for the "%content_type" content type?', ['%content_type' => $this->contentType->label()]);
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
    $description = '<p>' . $this->t('This action will delete the Node Revision Delete configuration for the "@content_type" content type, if this action take place the content type will not be available for revision deletion.', ['@content_type' => $this->contentType->label()]) . '</p>';
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
    // Deleting the content type configuration.
    $this->nodeRevisionDelete->deleteContentTypeConfig($this->contentType->id());
    // Printing a confirmation message.
    $this->messenger()->addMessage($this->t('The Node Revision Delete configuration for the "@content_type" content type has been deleted.', ['@content_type' => $this->contentType->label()]));
    // Redirecting.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
