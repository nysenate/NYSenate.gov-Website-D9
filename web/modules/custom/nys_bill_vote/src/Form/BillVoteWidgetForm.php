<?php

namespace Drupal\nys_bill_vote\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nys_bills\BillsHelper;
use Drupal\nys_subscriptions\SubscriptionInterface;
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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * NYS Bills Helper service.
   *
   * @var \Drupal\nys_bills\BillsHelper
   */
  protected BillsHelper $billHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->billVoteHelper = $container->get('nys_bill_vote.bill_vote');
    $instance->currentUser = $container->get('current_user');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->formBuilder = $container->get('form_builder');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->emailValidator = $container->get('email.validator');
    $instance->billHelper = $container->get('nys_bill.bills_helper');
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

    $form_state->addBuildInfo('is_embed', $parameter['is_embed'] ?? FALSE);

    $form['#id'] = 'nys-bill-vote-vote-widget-' . $node_id;

    $label = $this->billVoteHelper->getVotedLabel($default_value);

    $library[] = 'nys_bill_vote/bill_vote';
    $library[] = !$parameter['simple_mode']
      ? 'nysenate_theme/bill-vote-widget'
      : 'nysenate_theme/bill-vote-widget-simple';

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
              ($default_value === 'yes') ? 'current-vote' : '',
              ($default_value === 'no') ? 'change-vote' : '',
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
              ($default_value === 'no') ? 'current-vote' : '',
              ($default_value === 'yes') ? 'change-vote' : '',
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
        'library' => $library,
        'drupalSettings' => [
          'settings' => [
            'is_logged_in' => $this->currentUser->isAuthenticated(),
            'auto_subscribe' => TRUE,
          ],
        ],
      ],
    ];

    $this->addSubscriptionForm($form, $form_state, $node_id);
    if ($parameter['simple_mode']) {
      $form['nys_bill_vote_container']['nys_bill_vote_button_wrapper']['nys_bill_subscribe']['#access'] = FALSE;
      $form['nys_bill_vote_container']['nys_bill_vote_label']['#markup'] = '<div class="field__label">Do you support this bill?</div>';
    }

    $form['#cache'] = ['max-age' => 0];

    if ($parameter['is_embed']) {
      $form['#attributes']['class'][] = 'nys-bill-vote-form-embedded';
    }

    return $form;
  }

  /**
   * Insert Subsciption form fields.
   */
  public function addSubscriptionForm(&$form, FormStateInterface $form_state, $node_id = '') {
    $settings = $form_state->getBuildInfo();

    // If we have a node id, load that node.  Otherwise, use the current.
    $ref_node = !empty($node_id) ? $this->entityTypeManager->getStorage('node')->load($node_id)
        : $this->routeMatch->getParameter('node');

    // If the nid matches the current node's id, then this is not an embed.
    $is_embed = FALSE;
    if ($settings['is_embed']) {
      $is_embed = TRUE;
      $form_state->addBuildInfo('is_embed', TRUE);
    }

    $tid = $ref_node->field_bill_multi_session_root->target_id ?? NULL;

    // Act only if there's a node id and a taxonomy term id.
    if ($tid && $ref_node) {
      $form_state->addBuildInfo('tid', $tid);

      // Check if already subscribed.
      if ($this->billHelper->findSubscription($ref_node)) {
        $nys_bill_subscribe = [
          '#type' => 'markup',
          '#markup' => '<hr /><div class="subscribe_result">You Are Subscribed.</div>',
        ];
      }
      else {
        $nys_bill_subscribe = [
          '#uses_button_tag' => TRUE,
          '#type' => 'button',
          '#attributes' => [
            'class' => ['c-block--btn', 'nys-subscribe-button'],
            'value' => 'subscribe',
            'type' => 'submit',
          ],
          '#id' => 'edit-nys-bill-subscribe-' . $node_id,
          '#value' => 'Subscribe',
          '#ajax' => [
            'callback' => [$this, 'subscribeAjaxSubmit'],
            'wrapper' => 'edit-nys-bill-subscribe-' . $node_id,
          ],
          '#weight' => $is_embed ? 2 : 5,
        ];
      }

      // Construct the new form controls.
      $nys_subscribe_form = [
        'nys_bill_subscribe' => $nys_bill_subscribe,
        'nid' => [
          '#type' => 'hidden',
          '#value' => $node_id,
        ],
        'tid' => [
          '#type' => 'hidden',
          '#value' => $tid,
        ],
      ];

      // For embedded forms, modify the form style to support
      // the additional button.
      if ($is_embed) {
        $form['#attributes']['class'][] = 'nys-bill-vote-form-embedded';
        $form['nys_bill_vote_container']['nys_bill_vote_button_wrapper']['#weight'] = 1;
        $form['nys_bill_vote_container']['nys_bill_vote_button_wrapper']['nys_bill_vote_yes']['#weight'] = 3;
        $form['nys_bill_vote_container']['nys_bill_vote_button_wrapper']['nys_bill_vote_no']['#weight'] = 4;
        $form['nys_bill_vote_container']['nys_bill_vote_button_wrapper'] += $nys_subscribe_form;
      }
      // For bill pages, set a new container to hold the subscribe controls.
      else {
        $newform = [
          '#type' => 'container',
          '#attributes' => ['class' => ['nys-bill-subscribe']],
          '#id' => 'edit-nys-bill-subscribe-container-' . $node_id,
          'nys_bill_subscribe_title' => [
            '#markup' => '<div class="nys-bill-subscribe-beta"><a href="/citizen-guide/bill-alerts" style="color: #ffffff; font-weight: bold">BETA ⓘ</a></div><div class="nys-bill-subscribe-title">' . 'Get Status Alerts for ' . $ref_node->label() . '</div>',
          ],
        ];
        if (!$this->currentUser->isAuthenticated()) {
          $newform['email_form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['subscribe_email_container']],
            'email' => [
              '#type' => 'textfield',
              '#title' => $this->t('Email Address'),
              '#name' => 'email',
              '#size' => 20,
              '#id' => 'edit-email-address-entry-' . $node_id,
            ],
          ];
        }
        $form['nys_bill_subscribe_container'] = $newform + $nys_subscribe_form;
      }
    }
  }

  /**
   * AJAX Callback function for the buttons.
   */
  public function voteAjaxCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Redirect to registration page for anonymous users.
    if (!$this->currentUser->isAuthenticated()) {
      $url = Url::fromRoute('user.register');
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);

      return $response;
    }

    $settings = $form_state->getBuildInfo();
    $user_input = $form_state->getUserInput();
    $triggering_element = $form_state->getTriggeringElement();
    $value = $triggering_element['#value'];
    $id = $triggering_element['#id'];

    $bill_path = '/node/' . (empty($user_input['nid']) ? $settings['entity_id'] : $user_input['nid']);

    $intent = $this->billVoteHelper->getIntentFromVote($value);

    if ($settings['is_embed']) {
      $url = Url::fromUserInput($bill_path);
      $command = new RedirectCommand($url->toString() . '?intent=' . $intent);
      $response->addCommand($command);
      return $response;
    }

    $vote_args = [
      '#' . $id,
      $this->billVoteHelper->getVotedLabel($intent)->__toString(),
      $intent,
    ];
    $response->addCommand(new InvokeCommand($id, 'nysBillVoteUpdate', $vote_args));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribeAjaxSubmit(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());
    $values = $form_state->getValues();
    $settings = $form_state->getBuildInfo();

    // Get the entered email address, nid, and tid.  We need to use input here
    // because the two email controls were added dynamically through AJAX.  They
    // are not part of the original form definition.
    $email_address = trim($values['email'] ?? '');
    $tid = (int) ($values['tid'] ?? 0);
    $nid = (int) ($values['nid'] ?? 0);

    // If the user is logged in, revert to that email address.
    if ($this->currentUser->isAuthenticated()) {
      /**
       * @var \Drupal\user\UserInterface $user
       */
      $email_address = $user->getEmail();
    }

    // Check for an embedded form.
    $is_embed = $settings['is_embed'] ?? TRUE;

    if ($tid && $nid) {

      // Also create the parent ID we'll use to target elements
      // in the AJAX return.
      $parent_id = '#nys-bill-vote-vote-widget-' . $nid;

      // If this is an embedded form, and no email address is available,
      // redirect to the bill node instead.
      // This mimics bill voting behavior.
      if (empty($email_address) && $is_embed) {
        $bill_path = '/node/' . $settings['entity_id'];
        $url = Url::fromUserInput($bill_path);
        $command = new RedirectCommand($url->toString());
        $response->addCommand($command);
        return $response;
      }

      // If the email address is not valid, return an error.
      if (!$this->emailValidator->isValid($email_address)) {
        $form_error = [
          'email_error_markup' => [
            '#type' => 'markup',
            '#markup' => '<div class="subscribe_email_error">Invalid email address. Please use a valid email address.</div>',
          ],
        ];

        $response->addCommand(new RemoveCommand($parent_id . ' .subscribe_email_error'));
        $response->addCommand(new BeforeCommand($parent_id . ' .nys-subscribe-button', $form_error));

        return $response;
      }

      // Everything is awesome.  Generate the subscription.
      $this->subscriptionSignup($nid, $email_address);

      $form_is_awesome = [
        'sub_ok' => [
          '#type' => 'markup',
          '#markup' => '<hr /><div class="subscribe_result">You Are Subscribed.</div>',
        ],
      ];

      $response->addCommand(new BeforeCommand($parent_id . ' .nys-subscribe-button', $form_is_awesome));
      $response->addCommand(new RemoveCommand($parent_id . ' .subscribe_email_error'));
      $response->addCommand(new RemoveCommand($parent_id . ' .nys-subscribe-button'));
      return $response;
    }

    return $response;
  }

  /**
   * Sign up a subscription for the bill.
   */
  public function subscriptionSignup($nid, $email_address): ?SubscriptionInterface {
    try {
      /**
       * @var \Drupal\node\NodeInterface $bill
       */
      $bill = $this->entityTypeManager->getStorage('node')->load($nid);
    }
    catch (\Throwable) {
      $this->logger('BillVoteWidgetForm')->error('Could not load node storage');
      return NULL;
    }
    return $this->billHelper->subscribeToBill($bill, $email_address);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo This method comes from nys_accumulator custom module.
    // @phpstan-ignore-next-line
    // nyslog();
    $build_info = $form_state->getBuildInfo();
    $element = $form_state->getTriggeringElement();
    $this->billVoteHelper->processVote($build_info['entity_type'], $build_info['entity_id'], $element['value']);
  }

}
