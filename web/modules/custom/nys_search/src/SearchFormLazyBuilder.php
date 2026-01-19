<?php

namespace Drupal\nys_search;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\nys_search\Form\GlobalSearchForm;

/**
 * Lazy builder for the global search form.
 */
class SearchFormLazyBuilder implements TrustedCallbackInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Constructs a new SearchFormLazyBuilder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

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
