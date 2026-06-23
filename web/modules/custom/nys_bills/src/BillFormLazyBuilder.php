<?php

namespace Drupal\nys_bills;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Lazy builder for the per-user bill contact/action form.
 *
 * BillForm pre-populates fields with the current user's name, address, email,
 * and senator information. Wrapping it in a lazy builder keeps this
 * user-specific state out of the globally-cached page response so that bill
 * display pages can be served from the anonymous page cache.
 */
class BillFormLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new BillFormLazyBuilder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected FormBuilderInterface $formBuilder,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderBillForm'];
  }

  /**
   * Renders the bill contact/action form for a given bill node.
   *
   * @param int|string $nodeId
   *   The node ID of the bill.
   *
   * @return array
   *   A render array for the bill form, varying by user.
   */
  public function renderBillForm(int|string $nodeId): array {
    $node = $this->entityTypeManager->getStorage('node')->load($nodeId);
    if ($node === NULL) {
      return [];
    }

    $form = $this->formBuilder->getForm(
      'Drupal\nys_bills\Form\BillForm',
      $node
    );

    // Ensure the user cache context is explicit so the render cache creates
    // separate entries per user rather than poisoning a shared entry.
    $form['#cache']['contexts'][] = 'user';

    return $form;
  }

}
