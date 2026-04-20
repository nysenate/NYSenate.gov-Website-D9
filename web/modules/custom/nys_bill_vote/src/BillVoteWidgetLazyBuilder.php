<?php

namespace Drupal\nys_bill_vote;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Lazy builder for the per-user bill vote widget form.
 *
 * Wrapping BillVoteWidgetForm in a lazy builder isolates the user-specific
 * vote and subscription state from the globally-cached page output, allowing
 * bill display pages to be page-cached anonymously while authenticated users
 * receive their personalised vote/subscribe state via the dynamic page cache.
 */
class BillVoteWidgetLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new BillVoteWidgetLazyBuilder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct(
    protected FormBuilderInterface $formBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderVoteWidget'];
  }

  /**
   * Renders the bill vote widget form for a given bill node.
   *
   * @param int|string $nodeId
   *   The node ID of the bill.
   * @param bool $isEmbed
   *   Whether the widget is embedded in another page context.
   * @param bool $simpleMode
   *   Whether to use the simplified vote widget variant.
   *
   * @return array
   *   A render array for the vote widget, varying by user.
   */
  public function renderVoteWidget(int|string $nodeId, bool $isEmbed = FALSE, bool $simpleMode = FALSE): array {
    $settings = [
      'entity_type' => 'bill',
      'entity_id' => $nodeId,
      'is_embed' => $isEmbed,
      'simple_mode' => $simpleMode,
    ];

    $form = $this->formBuilder->getForm(
      'Drupal\nys_bill_vote\Form\BillVoteWidgetForm',
      $settings
    );

    // Ensure the user cache context is explicit so the render cache creates
    // separate entries per user rather than poisoning a shared entry.
    $form['#cache']['contexts'][] = 'user';

    return $form;
  }

}
