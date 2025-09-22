<?php

namespace Drupal\nys_openleg\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_openleg\StatuteHelper;

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
    $search_term = (string) ($form_state->getBuildInfo()['args'][0] ?? '');

    return [
      'title' => ['#markup' => '<h3 tabindex="0" class="search-title">Search OpenLegislation Statutes</h3>'],
      'search_form_container' => [
        '#type' => 'container',
        'search_term' => [
          '#type' => 'textfield',
          '#title' => 'Search Term',
          '#default_value' => Xss::filter($search_term),
        ],
        'go' => [
          '#type' => 'submit',
          '#value' => 'Search',
        ],
      ],
      '#action' => StatuteHelper::baseUrl() . '/search',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
  }

}
