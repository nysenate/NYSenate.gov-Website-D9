<?php

namespace Drupal\nys_bill_vote\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nys_bills\BillsHelper;
use Drupal\nys_subscriptions\SubscriptionInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds bill widgets and processes authenticated vote submissions.
 *
 * The lazy builder displays this form; bill_vote.widget handles its POSTs.
 * Initial Aye/Nay callbacks only request confirmation or redirect to an intent
 * URL. Votes are saved in submitForm(), after Form API's session-token checks
 * and this form's vote-specific validation. AJAX callbacks present the result.
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
   *
   * Lazy rendering supplies widget settings; route reconstruction supplies the
   * upcast bill and reads presentation flags from the request query instead.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parameter = [], ?NodeInterface $bill_node = NULL) {
    if ($bill_node) {
      $parameter = [
        'entity_type' => 'bill',
        'entity_id' => $bill_node->id(),
        'is_embed' => $this->getRequest()->query->getBoolean('is_embed'),
        'simple_mode' => $this->getRequest()->query->getBoolean('simple_mode'),
      ];
      $form_state->addBuildInfo('args', [$parameter]);
    }
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
    $form['#method'] = 'post';
    // Process submissions once, rather than during page placeholder rendering.
    // These client-controlled flags preserve layout and embedded redirects;
    // they do not authorize writes or select a different bill.
    $widget_context = [
      'is_embed' => (int) ($parameter['is_embed'] ?? FALSE),
      'simple_mode' => (int) ($parameter['simple_mode'] ?? FALSE),
    ];
    $form['#action'] = Url::fromRoute('bill_vote.widget', ['bill_node' => $node_id], ['query' => $widget_context])->toString();
    $ajax_url = Url::fromRoute('bill_vote.widget', ['bill_node' => $node_id]);
    $ajax_options = ['query' => ['ajax_form' => 1] + $widget_context];

    $label = $parameter['simple_mode']
      ? $this->billVoteHelper->getVotedLabel()
      : $this->billVoteHelper->getVotedLabel($default_value);

    $library[] = 'nys_bill_vote/bill_vote';
    $library[] = $parameter['simple_mode']
      ? 'nysenate_theme/bill-vote-widget-simple'
      : 'nysenate_theme/bill-vote-widget';

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
        '#markup' => '<h2 class="c-bill-polling--cta">' . $label . '</h2>',
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
          ],
          '#id' => 'edit-nys-bill-vote-yes-' . $node_id,
          '#value' => 'Aye',
          '#ajax' => [
            'url' => $ajax_url,
            'options' => $ajax_options,
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
          ],
          '#id' => 'edit-nys-bill-vote-no-' . $node_id,
          '#value' => 'Nay',
          '#ajax' => [
            'url' => $ajax_url,
            'options' => $ajax_options,
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
          ],
        ],
      ],
    ];

    // The dialog triggers these real Form API submit controls. Hiding them is
    // only a UI detail; Form API validates the token before running submitForm.
    foreach (['yes', 'no'] as $vote_value) {
      $form['confirm_' . $vote_value] = [
        '#type' => 'submit',
        '#name' => 'confirm_' . $vote_value,
        '#id' => 'edit-confirm-' . $vote_value . '-' . $node_id,
        '#value' => $vote_value === 'yes' ? $this->t('Confirm support') : $this->t('Confirm opposition'),
        '#vote_value' => $vote_value,
        '#attributes' => [
          'hidden' => TRUE,
          'data-confirm-vote' => $vote_value,
        ],
        '#access' => $this->currentUser->isAuthenticated(),
        '#ajax' => [
          'url' => $ajax_url,
          'options' => $ajax_options,
          'callback' => [$this, 'confirmedVoteAjaxCallback'],
          'event' => 'click',
        ],
      ];
    }

    $this->addSubscriptionForm($form, $form_state, $node_id);

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
    $ajax_options = [
      'query' => [
        'ajax_form' => 1,
        'is_embed' => (int) ($settings['is_embed'] ?? FALSE),
        'simple_mode' => (int) ($settings['simple_mode'] ?? FALSE),
      ],
    ];

    // If we have a node id, load that node.  Otherwise, use the current.
    $ref_node = !empty($node_id)
      ? $this->entityTypeManager->getStorage('node')->load($node_id)
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
      $subscription = $this->billHelper->findSubscription($ref_node);
      if ($subscription && !$subscription->getCanceled()) {
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
            'url' => Url::fromRoute('bill_vote.widget', ['bill_node' => $node_id]),
            'options' => $ajax_options,
            'callback' => [$this, 'subscribeAjaxSubmit'],
            'wrapper' => 'edit-nys-bill-subscribe-' . $node_id,
          ],
          '#weight' => $is_embed ? 2 : 5,
        ];
      }

      if ($settings['simple_mode']) {
        $nys_bill_subscribe['#access'] = FALSE;
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
            '#markup' => '<h2 class="nys-bill-subscribe-title">Get Status Alerts for ' . $ref_node->label() . '</h2>',
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
   * Requests confirmation or redirects without recording the proposed vote.
   */
  public function voteAjaxCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new PrependCommand('#' . $form['#id'], ['#type' => 'status_messages']));
      return $response;
    }

    // Redirect to registration page for anonymous users.
    if (!$this->currentUser->isAuthenticated()) {
      $url = Url::fromRoute('user.register');
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);

      return $response;
    }

    $settings = $form_state->getBuildInfo();
    $triggering_element = $form_state->getTriggeringElement();
    $value = $triggering_element['#value'];
    $id = $triggering_element['#id'];

    $intent = $value === 'Aye' ? 'support' : 'oppose';
    if (!empty($settings['is_embed'])) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $settings['entity_id']], ['query' => ['intent' => $intent]]);
      $response->addCommand(new RedirectCommand($url->toString()));
      return $response;
    }

    $bill = $this->entityTypeManager->getStorage('node')->load($settings['entity_id']);
    $response->addCommand(new InvokeCommand('#' . $id, 'nysBillVoteConfirm', [
      $value === 'Aye' ? 'yes' : 'no',
      $bill->label(),
    ]));

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
   *
   * Adds vote-specific checks to Form API validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (!isset($element['#vote_value'])) {
      return;
    }

    $settings = $form_state->getBuildInfo();
    $bill = $this->entityTypeManager->getStorage('node')->load($settings['entity_id']);
    if (!$this->currentUser->isAuthenticated()
      || !$bill instanceof NodeInterface
      || $bill->bundle() !== 'bill'
      || !$bill->access('view', $this->currentUser)
      || !in_array($element['#vote_value'], ['yes', 'no'], TRUE)) {
      $form_state->setErrorByName('nys_bill_vote', $this->t('Unable to record this position. Please reload the bill page and try again.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Form API calls this handler only after successful validation. Subscription
   * status is reported separately: a recorded vote does not imply enrollment
   * in bill alerts when the constituent has opted out or canceled.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (!isset($element['#vote_value'])) {
      return;
    }

    $settings = $form_state->getBuildInfo();
    $bill = $this->entityTypeManager->getStorage('node')->load($settings['entity_id']);
    $vote = $this->billVoteHelper->processVote($this->currentUser, $bill, $element['#vote_value']);
    $form_state->set('vote_recorded', $vote !== NULL);
    $subscription = $this->billHelper->findSubscription($bill, $this->currentUser);
    $form_state->set('vote_subscribed', $subscription && $subscription->getConfirmed() && !$subscription->getCanceled());
    if ($vote === NULL) {
      $this->messenger()->addError($this->t('Your position could not be recorded. Please try again.'));
    }
  }

  /**
   * Updates the voting UI only after a validated, successful submission.
   *
   * AJAX callbacks can run after validation errors, so a response alone is not
   * proof that submitForm() ran or that the vote was saved.
   */
  public function confirmedVoteAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors() || !$form_state->get('vote_recorded')) {
      $response->addCommand(new PrependCommand('#' . $form['#id'], ['#type' => 'status_messages']));
      return $response;
    }

    $value = $form_state->getTriggeringElement()['#vote_value'];
    $subscribed = (bool) $form_state->get('vote_subscribed');
    $response->addCommand(new InvokeCommand('#' . $form['#id'], 'nysBillVoteRecorded', [$value, $subscribed]));
    return $response;
  }

}
