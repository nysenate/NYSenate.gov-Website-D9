<?php

namespace Drupal\nys_bills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Bill Form class.
 */
class BillForm extends FormBase {

  use StringTranslationTrait;

  /**
   * The BillVoteHelper class variable.
   *
   * @var \Drupal\nys_bill_vote\BillVoteHelper
   */
  protected $billVoteHelper;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Default object for form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->billVoteHelper = $container->get('nys_bill_vote.bill_vote');
    $instance->currentUser = $container->get('current_user');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_bill_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {

    $form['message_header'] = [
      '#type' => 'markup',
      '#markup' => t('<hr><p>Include a custom message for your Senator? (Optional)</p>'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => '',
      '#description' => $this->t('Enter a message to your senator. Many New Yorkers use this to share the reasoning behind their support or opposition to the bill. Others might share a personal anecdote about how the bill would affect them or people they care about.'),
    ];

    if ($this->currentUser->isAuthenticated()) {
      $submit_value = 'Send Message';
    }
    else {
      $submit_value = 'Submit Form';
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_value,
      '#weight' => 15,
      '#attributes' => [
        'class' => [
          'c-btn--cta',
          'c-btn--cta__sign',
          'flag-wrapper',
          'flag-sign-bill',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
