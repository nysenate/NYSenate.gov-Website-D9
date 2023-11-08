<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\node\Entity\Node;

/**
 * Builds the site-wide search form.
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
    if (($node instanceof Node) && ($node->bundle() == 'microsite_page')) {
      $senator_term = $node->field_senator_multiref?->entity ?? NULL;
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
    $senator_param = $senator ? '&senator=' . $senator : '';
    $url = '/search/global/result?full_text=' . $keys . $senator_param;
    $response = new TrustedRedirectResponse($url);
    $response->send();
  }

}
