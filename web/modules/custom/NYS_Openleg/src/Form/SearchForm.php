<?php

namespace Drupal\NYS_Openleg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\NYS_Openleg\StatuteHelper;

/**
 * Class SearchForm.
 *
 * Form-handling class for NYS Openleg search.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_openleg_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // In order of precedence, look for a search term in build_info,
    // form post, and query string parameter.  First one wins.
    $search_term = $form_state->getBuildInfo()['args'][0] ?? '';

    return [
      'title' => ['#markup' => '<div class="search-title">Search OpenLegislation Statutes</div>'],
      'search_term' => [
        '#type' => 'textfield',
        '#title' => 'Search Term',
        '#default_value' => (string) $search_term,
      ],
      'go' => [
        '#type' => 'submit',
        '#value' => 'Search',
      ],
      '#action' => StatuteHelper::PATH_PREFIX . '/search',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
  }

}
