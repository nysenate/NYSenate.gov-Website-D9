<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

/**
 * Allow for calling the SAGE API and viewing the return.
 */
class GlobalSearchForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'nys_search.global_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'search-form ';
    $form['#attributes']['class'][] = 'c-site-search';
    $form['#attributes']['accept-charset'] = 'UTF-8';
    $form['#method'] = 'post';

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Search',
      '#attributes' => [
        'class' => ['c-site-search--title'],
      ],
    ];

    $form['keys'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => 'Search',
        'class' => ['c-site-search--box', 'icon_after__search', 'form-text'],
        'size' => '50',
        'maxlength' => '255',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
      '#attributes' => [
        'class' => [
          'c-site-search--btn',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keys = $form_state->getValue('keys');
    $response = new TrustedRedirectResponse(Url::fromUri('internal:/search/global/' . $keys)->toString());
    $response->send();
  }

}
