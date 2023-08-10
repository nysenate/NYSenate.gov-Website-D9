<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

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

    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node) && $node->bundle() == 'microsite_page') {
      $senator_term = ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty())
        ? $node->get('field_senator_multiref')->entity : [];
      if ($senator_term) {
        $form['senator'] = [
          '#type' => 'hidden',
          '#default_value' => $senator_term->id(),
        ];
      }
    }

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
    $senator = $form_state->getValue('senator');
    $senator_param = $senator ? '?f[0]=senator:' . $senator : '';
    $url = '/search/global/' . $keys . $senator_param;
    $response = new TrustedRedirectResponse($url);
    $response->send();
  }

}
