<?php

namespace Drupal\nys_bill_vote\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Bill Vote Widget form class.
 */
class BillVoteWidgetForm extends FormBase {

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
    return 'nys_bill_vote_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parameter = []) {
    // In this special case, just leave.
    // @todo This method comes from nys_utils.
    // @todo Uncomment the condition after testing.
    // @phpstan-ignore-next-line
    // if (senator_viewing_constituent_dashboard()) {
    // return $form;
    // }
    // Detect the build settings.
    $form_state->setBuildInfo(array_merge($this->billVoteHelper->widgetBuildSettings($form_state), $form_state->getBuildInfo()));

    // Now get the canonical information.
    $node_id = $form_state->getBuildInfo()['entity_id'];

    $default_vote = $this->billVoteHelper->getDefault('node', $node_id);
    $default_value = $this->billVoteHelper->getVal($default_vote);

    // Discover if a vote has been submitted.
    if (!empty($form_state->getValue('nys_bill_vote'))) {
      $default_value = $form_state->getValue('nys_bill_vote');
    }

    // Add the distinct class.
    $form['#attributes'] = [
      'class' => [
        'nys-bill-vote-form',
      ],
    ];

    $form['#id'] = 'nys-bill-vote-vote-widget-' . $node_id;

    $label = $this->billVoteHelper->getVotedLabel($default_value);

    // The main form.
    $form['nys_bill_vote_container'] = [
      // Main form attributes.
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'nys-bill-vote',
        ],
      ],
      '#id' => 'edit-nys-bill-vote-container-' . $node_id,

      // Primary label.
      'nys_bill_vote_label' => [
        '#markup' => '<p class="c-bill-polling--cta">' . $label . '</p>',
      ],

      'nys_bill_vote_button_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'button-wrapper',
          ],
        ],
        // The "Aye" button.
        'nys_bill_vote_yes' => [
          '#uses_button_tag' => TRUE,
          '#type' => 'button',
          '#attributes' => [
            'class' => [
              'c-block--btn',
              'c-half-btn',
              'c-half-btn--left',
              'nys-bill-vote-yes',
            ],
            // 'type' => 'submit',
          ],
          '#id' => 'edit-nys-bill-vote-yes-' . $node_id,
          '#value' => 'Aye',
          '#ajax' => [
            'callback' => [$this, 'voteAjaxCallback'],
            'event' => 'click',
          ],
        ],
        // The "Nay" button.
        'nys_bill_vote_no' => [
          '#uses_button_tag' => TRUE,
          '#type' => 'button',
          '#attributes' => [
            'class' => [
              'c-block--btn',
              'c-half-btn',
              'c-half-btn--right',
              'nys-bill-vote-no',
            ],
            // 'type' => 'submit',
          ],
          '#id' => 'edit-nys-bill-vote-no-' . $node_id,
          '#value' => 'Nay',
          '#ajax' => [
            'callback' => [$this, 'voteAjaxCallback'],
            'event' => 'click',
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'nys_bill_vote/bill_vote',
        ],
        'drupalSettings' => [
          'settings' => [
            'is_logged_in' => $this->currentUser->isAuthenticated(),
            'auto_subscribe' => TRUE,
          ],
        ],
      ],
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  /**
   * AJAX Callback function for the buttons.
   */
  public function voteAjaxCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $build_info = $form_state->getBuildInfo();
    $triggering_element = $form_state->getTriggeringElement();
    $value = $triggering_element['#value'];
    $id = $triggering_element['#id'];

    // We want to process the vote if the user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      $this->billVoteHelper->processVote($build_info['entity_type'], $build_info['entity_id'], $value);
      $form['nys_bill_vote']['#default_value'] = $this->billVoteHelper->getVal($this->billVoteHelper->getDefault($build_info['entity_type'], $build_info['entity_id']));
      $form['nys_bill_vote']['#options'] = $this->billVoteHelper->getOptions();
    }

    // If the user is on a page that isn't the bill node, send them there.
    $test_action = $this->formBuilder->renderPlaceholderFormAction()['#markup'];
    $node_match = $this->aliasManager->getPathByAlias($test_action);
    $bill_path = '/node/' . $build_info['entity_id'];

    $options['query'] = [
      'intent' => $this->billVoteHelper->getIntentFromVote($value),
    ];

    $url = Url::fromUserInput($bill_path, $options);
    $command = new RedirectCommand($url->toString());
    $response->addCommand($command);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo This method comes from nys_accumulator custom module.
    // @phpstan-ignore-next-line
    // nyslog();
    $node = $form_state->getFormObject();
    $build_info = $form_state->getBuildInfo();
    $element = $form_state->getTriggeringElement();
    $this->billVoteHelper->processVote($build_info['entity_type'], $build_info['entity_id'], $element['value']);
  }

}
