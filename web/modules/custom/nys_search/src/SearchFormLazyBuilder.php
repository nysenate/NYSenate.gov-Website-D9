<?php

namespace Drupal\nys_search;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\nys_search\Form\GlobalSearchForm;

/**
 * Lazy builder for the global search form.
 *
 * Renders the search form after page cache, allowing the page to be cached
 * while the form (with its CSRF token) is rendered per-user.
 */
class SearchFormLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new SearchFormLazyBuilder.
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
    return ['renderSearchForm'];
  }

  /**
   * Renders the global search form.
   *
   * @return array
   *   A render array for the search form.
   */
  public function renderSearchForm(): array {
    return $this->formBuilder->getForm(GlobalSearchForm::class);
  }

}
