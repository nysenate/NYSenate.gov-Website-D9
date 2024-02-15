<?php

namespace Drupal\views_load_more\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "load_more",
 *   title = @Translation("Load more pager"),
 *   short_title = @Translation("Load more"),
 *   help = @Translation("Paged output, each page loaded via AJAX."),
 *   theme = "views_load_more_pager",
 *   register_theme = FALSE
 * )
 */
class LoadMore extends Full {

  /**
   * The default jQuery selector for views content.
   */
  const DEFAULT_CONTENT_SELECTOR = '> .view-content';

  /**
   * The default jQuery selector for view pager.
   */
  const DEFAULT_PAGER_SELECTOR = '.pager--load-more';

  /**
   * Overrides \Drupal\views\Plugin\views\pager\Full::summaryTitle().
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], 'Load more pager, @count item, skip @skip', 'Load more pager, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
    return $this->formatPlural($this->options['items_per_page'], 'Load more pager, @count item', 'Load more pager, @count items', array('@count' => $this->options['items_per_page']));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Full::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['more_button_text'] = array('default' => $this->t('Load more'));
    $options['end_text'] = array('default' => '');

    $options['advanced']['contains']['content_selector'] = array('default' => '');
    $options['advanced']['contains']['pager_selector'] = array('default' => '');

    $options['effects']['contains']['type'] = array('default' => '');
    $options['effects']['contains']['speed'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Full::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // A couple settings are irrelevant for a Load More pager
    unset($form['tags']);
    unset($form['quantity']);

    // Keep items per page as the first form element on the page followed by
    // the option to change the 'load more' button text
    $form['items_per_page']['#weight'] = -2;

    // Option for users to specify the text used on the 'load more' button.
    $form['more_button_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Load more text'),
      '#description' => $this->t('The text that will be displayed on the link that is used to load more elements. For example "Show me more"'),
      '#default_value' => $this->options['more_button_text'] ? $this->options['more_button_text'] : $this->t('Load more'),
    );

    // Option for users to specify the text shown when there are no more results
    $form['end_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Finished text'),
      '#description' => $this->t('Optionally specify the text that is shown to the user in place of the pager link when the user has reached the end of the list, eg. "No more results".'),
      '#default_value' => $this->options['end_text'] ? $this->options['end_text'] : '',
    );

    // Adjust exposed details element weight
    $form['expose']['#weight'] = 10;

    // Advanced options, override default selectors.
    $form['advanced'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' =>  $this->t('Advanced Options'),
      '#description' => $this->t('Configure advanced options.'),
      '#weight' => 11,
    );

    // Option to specify the content_selector, which is the wrapping div for views
    // rows.  This allows the JS to both find new rows on next pages and know
    // where to put them in the page.
    $form['advanced']['content_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content selector'),
      '#description' => $this->t('jQuery selector for the rows wrapper, relative to the view container.  Use when overriding the views markup.  Note that Views Load More requires a wrapping element for the rows.  Unless specified, Views Load More will use <strong><code>@content_selector</code></strong>.', array('@content_selector' => LoadMore::DEFAULT_CONTENT_SELECTOR)),
      '#default_value' => $this->options['advanced']['content_selector'],
    );

    // Option to specify the pager_selector, which is the pager relative to the
    // view container.
    $form['advanced']['pager_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pager selector'),
      '#description' => $this->t('jQuery selector for the pager, relative to the view container.  Use when overriding the pager markup so that Views Load More knows where to find and how to replace the pager.  Unless specified, Views Load More will use <strong><code>@pager_selector</code></strong>.', array('@pager_selector' => LoadMore::DEFAULT_PAGER_SELECTOR)),
      '#default_value' => $this->options['advanced']['pager_selector'],
    );

    // Affect the way that Views Load More adds new rows
    $form['effects'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' =>  $this->t('JQuery Effects'),
      '#weight' => 12,
    );

    $form['effects']['type'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('None'),
        'fadeIn' => $this->t('Fade'),
        'slideDown' => $this->t('Slide'),
      ),
      '#default_vaue' => 'none',
      '#title' => $this->t('Effect Type'),
      '#default_value' => $this->options['effects']['type'],
      '#description' => $this->t('jQuery animation to use to show new rows.'),
    );

    $form['effects']['speed'] = array(
      '#type' => 'select',
      '#options' => array(
        'slow' => $this->t('Slow'),
        'fast' => $this->t('Fast'),
      ),
      '#states' => array(
        'visible' => array(
          array('#edit-pager-options-effects-type' => array('value' => 'fade')),
          array('#edit-pager-options-effects-type' => array('value' => 'slide')),
        ),
      ),
      '#title' => $this->t('Effect Speed'),
      '#default_value' => $this->options['effects']['speed'],
    );
  }

  /**
   * {@inheritdoc}
   */
  function render($parameters) {
    $output = array(
      '#theme' => $this->themeFunctions(),
      '#element' => $this->options['id'],
      '#parameters' => $parameters,
      '#more_button_text' => $this->options['more_button_text'],
      '#end_text' => $this->options['end_text'],
    );

    return $output;
  }
}
