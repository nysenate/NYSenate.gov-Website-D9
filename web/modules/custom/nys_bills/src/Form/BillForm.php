<?php

namespace Drupal\nys_bills\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\private_message\Entity\PrivateMessage;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The NYS user helper.
   *
   * @var \Drupal\nys_users\UsersHelper
   */
  protected $nysUserHelper;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Default object for messenger serivce.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messengerService;

  /**
   * Default object for private_message.thread_manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  protected $privateMessageThreadManager;

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessageService;

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
    $instance->nysUserHelper = $container->get('nys_users.user_helper');
    $instance->emailValidator = $container->get('email.validator');
    $instance->flagService = $container->get('flag');
    $instance->messengerService = $container->get('messenger');
    $instance->privateMessageThreadManager = $container->get('private_message.thread_manager');
    $instance->privateMessageService = $container->get('private_message.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_bills_bill_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {

    $vote_results = $this->justVoted($node->id());
    if ($vote_results !== FALSE && $vote_results['voted'] === TRUE) {
      return $this->billVotedForm($form, $form_state, $vote_results, $this->hasThread($node->id()));
    }

    $form_state->setStorage(['node' => $node]);
    $form['pass_thru_url'] = [
      '#type' => 'hidden',
      '#default_value' => Url::fromRoute('<current>')->toString(),
    ];

    $form_info = [];

    // Initialize values just in case user is anonymous or out-of-state.
    $senator = $this->t('the senator');
    $printno = $node->label();

    if ($this->currentUser->isAuthenticated()) {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      // Only in-state users will have a senator assigned.
      if (!$this->nysUserHelper->isOutOfState()) {
        $senator = $this->nysUserHelper->getSenator($user);
        if ($senator !== NULL) {
          $senator = 'Sen. ' . $senator->label();
        }
      }

      // User's address is an array of location data. We do this first to
      // leverage the foreach.
      if (!$user->field_address->isEmpty()) {
        foreach ($user->field_address->getValue()[0] as $key => $value) {
          $form_info[$key] = $value;
        }
      }

      if (!$user->field_first_name->isEmpty()) {
        $form_info['first_name'] = $user->field_first_name->value;
      }
      if (!$user->field_last_name->isEmpty()) {
        $form_info['family_name'] = $user->field_last_name->value;
      }
      $form_info['email'] = $user->getEmail();

      // If the user is already logged in,
      // personal info fields should be hidden.
      $text_type = 'hidden';
      $form_markup = '<p>' . $this->t('Would you like to include a private message to @senator on <span class=\"bill-widget-status\"></span> @printno?', [
        '@senator' => $senator,
        '@printno' => $printno,
      ]) . '</p>';
    }
    else {
      $text_type = 'textfield';
      $form_markup = $this->t('<p>Please enter your contact information</p>');
    }

    if ($this->nysUserHelper->isOutOfState() && $this->currentUser->isAuthenticated()) {
      $form_markup = '<p>' . $this->t('Thank you for your participation.') . '</p>';
    }

    if (!empty($form_state->getErrors())) {
      $form_markup = $form_state->getErrors();
    }

    $form['#attributes']['class'][] = 'registration-form';

    $form['header_text'] = [
      '#markup' => $form_markup,
    ];

    $form['first_name'] = [
      '#type' => $text_type,
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#size' => 35,
      '#default_value' => $form_info['first_name'] ?? '',
    ];

    $form['last_name'] = [
      '#type' => $text_type,
      '#title' => $this->t('Last Name'),
      '#required' => !empty($form_info['family_name']),
      '#maxlength' => 255,
      '#size' => 35,
      '#default_value' => $form_info['family_name'] ?? '',
    ];

    $form['email'] = [
      '#type' => $text_type,
      '#title' => t('Email Address'),
      '#required' => TRUE,
      '#description' => t('A valid email address is required.'),
      '#maxlength' => 254,
      '#size' => 30,
      '#default_value' => $form_info['email'] ?? '',
    ];

    $form['address'] = [
      '#type' => $text_type,
      '#description' => 'Home address is used to determine the senate district in which you reside. Your support or opposition to this bill is then shared immediately with the senator who represents you.',
    ];

    if (!$this->currentUser->isAuthenticated()) {
      $form['options_header'] = [
        '#type' => 'markup',
        '#markup' => t('<hr><p>Optional services from the NY State Senate:</p>'),
      ];

      $form['subscribe'] = [
        '#type' => 'checkbox',
        '#default_value' => 1,
        '#description' => t('Send me alerts for this bill. I can unsubscribe at any time. <a href="/citizen-guide/bill-alerts">Learn more</a>.'),
      ];
    }

    $form['register'] = [
      '#type' => 'checkbox',
      '#default_value' => 1,
      '#description' => t('<strong>Create an account</strong>. An <a href="/citizen-guide">account</a> allows you to officially support or oppose key legislation, sign petitions with a single click, and follow issues, committees, and bills that matter to you. When you create an account, you agree to this platform\'s <a href="/policies-and-waivers">terms of participation</a>.'),
    ];

    if ($this->currentUser->isAuthenticated()) {
      unset($form['register']);
    }
    else {
      // Senator NID as Sponsor in case out of state user.
      $form['senator'] = [
        '#type' => 'hidden',
      ];
    }

    // Vote value for entries passed on submit to help with creating message
    // subject.
    $form['vote_value'] = [
      '#type' => 'hidden',
    ];

    // Out of state users do not have a senator and therefore cannot send a
    // message.
    if (!$this->currentUser->isAuthenticated() || !$this->nysUserHelper->isOutOfState()) {
      if (!$this->currentUser->isAuthenticated()) {
        $form['message_header'] = [
          '#type' => 'markup',
          '#markup' => t('<hr><p>Include a custom message for your Senator? (Optional)</p>'),
        ];
      }

      $form['message'] = [
        '#type' => 'textarea',
        '#title' => '',
        '#description' => t('Enter a message to your senator. Many New Yorkers use this to share the reasoning behind their support or opposition to the bill. Others might share a personal anecdote about how the bill would affect them or people they care about.'),
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
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_storage = $form_state->getStorage();

    if ($this->currentUser->isAuthenticated()) {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $email = $user->getEmail();
    }
    else {
      $email = $values['email'];
    }

    // Reset the return destination.
    $return_destination = NULL;
    if (!empty($values['pass_thru_url'])) {
      $return_destination = '?destination=/' . $values['pass_thru_url'];
    }

    // Validate the email address.
    if (!$this->emailValidator->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }
    elseif (user_load_by_mail($email) && !$this->currentUser->isAuthenticated()) {
      $href = '/user/login' . $return_destination;
      $form_state->setErrorByName('email', t('Our records show you already have an account. Please <a href="@href">log in</a> to continue', ['@href' => $href]));
    }
    elseif ($this->currentUser->isAuthenticated() && !empty($form_storage['node'])) {
      $node = $form_storage['node'];
      $flag = $this->flagService->getFlagById('follow_this_bill');
      $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if (!empty($this->flagService->getFlagging($flag, $node, $current_user))) {
        // @todo Detemine if authenticated users are not allowed
        // to change vote and can only vote once.
        // @phpstan-ignore-next-line
        // $form_state->setErrorByName('email',
        // $this->t('You have already supported or opposed this bill.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_storage = $form_state->getStorage();
    $user_storage = $this->entityTypeManager->getStorage('user');
    // Need the global user object.
    $user = $user_storage->load($this->currentUser->id());

    // Set up some easy references for later.
    $node = $form_storage['node'];
    $values = $form_state->getValues();
    $user_id = (int) ($this->currentUser->isAuthenticated() ? $user->id() : 0);
    $user_mail = $this->currentUser->isAuthenticated() ? $user->getEmail() : $values['email'];
    $node_tid = (int) $node->field_bill_multi_session_root->getValue()[0]['target_id'];

    // Get the vote options.
    $vote_options = $this->billVoteHelper->getOptions();

    $vote_index = $values['vote_value'];
    $vote_label = $vote_options[$vote_index];

    $form_state->setRedirectUrl(Url::fromUserInput(\Drupal::request()->get('pass_thru_url')));

    if ($this->currentUser->isAuthenticated()) {
      $the_vote = $this->billVoteHelper->processVote($user, $node, $vote_index);
      $senator = $this->nysUserHelper->getSenator($user);
    }

    // Send Private Message.
    if ($this->currentUser->isAuthenticated() && !empty($values['message'])) {
      // Only in-state users will have a senator assigned.
      if (!$this->nysUserHelper->isOutOfState()) {

        if ($senator !== NULL) {
          if ($senator->hasField('field_user_account') && !$senator->get('field_user_account')->isEmpty()) {
            $senator_user_id = $senator->field_user_account->target_id;
          }

          if (isset($senator_user_id)) {
            switch ($vote_index) {
              case 'yes':
                $vote_type = 'supported';
                break;

              case 'no':
                $vote_type = 'opposed';
                break;

              default:
                $vote_type = 'sent a message regarding';
                break;
            }

            $subject = $values['first_name'] . ' ' . $values['last_name'] . ' ' .
              $vote_type . ' ' . $node->label();

            // Create the private message entity.
            $message = PrivateMessage::create([
              'message' => $values['message'],
              'field_subject' => $subject,
              'field_to' => [$senator_user_id],
              'field_bill' => [$node->id()],
            ]);
            $message->save();

            $recipients = [$user_storage->load($senator_user_id), $user];
            // Add it to the thread with the senator user.
            $this->privateMessageThreadManager->saveThread($message, $recipients);

            if (!empty($message->id())) {
              $this->messengerService->addStatus($this->t('Your message has been sent!'));
            }

            $url = Url::fromUserInput('/node/' . $node->id())->toString();
            $response = new RedirectResponse($url);
            $response->send();
          }
        }
      }
    }

    // @todo handle subscription and account creation.
  }

  /**
   * Generates the bill vote thank you message form.
   */
  public function billVotedForm($form, &$form_state, $vote_results, $has_thread) {
    if (($vote_results === FALSE || $vote_results['voted'] == FALSE) && $has_thread === FALSE) {
      return $form;
    }

    $alert = 'Thank you for your participation.';

    $form['msg'] = [
      '#markup' => '<div class="clearfix"></div><div class="l-messages"><div class="alert-box icon-before__petition"><div class="alert-box-message"><p>' . $alert . '</p></div></div></div>',
      '#weight' => 100,
    ];

    $form['uid'] = [
      '#type' => 'hidden',
      '#default_value' => $vote_results['uid'] ?? NULL,
    ];

    $form['vote_value'] = [
      '#type' => 'hidden',
      '#default_value' => $vote_results['vote_value'] ?? NULL,
    ];

    // Adds the javascript to setup the bill and scroll users to the message.
    $form['#attached']['library'][] = 'nys_bills/after_vote';

    return $form;
  }

  /**
   * Check if the user just casted a vote.
   */
  public function justVoted($entity_id) {
    $uid = $this->currentUser->id();
    /** @var \Drupal\votingapi\VoteStorage $vote_storage */
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    if ($uid === 0) {
      // Anonymous user.
      $user_votes = $vote_storage->getUserVotes($uid, 'nys_bill_vote', 'node', $entity_id, \Drupal::request()->getClientIp());
    }
    elseif ($uid > 0) {
      // Registered user.
      $user_votes = $vote_storage->getUserVotes($uid, 'nys_bill_vote', 'node', $entity_id);
    }

    $vote_value = NULL;
    $vote_entity = NULL;
    if (!empty($user_votes)) {
      /** @var \Drupal\votingapi\Entity\Vote $vote_entity */
      $vote_entity = $vote_storage->load(end($user_votes));
      $created = $vote_entity->getCreatedTime();
      // 4 secs buffer.
      if ($created > (time() - 4)) {
        $vote_value = (int) $vote_entity->getValue();
      }

    }

    if ($vote_value === 0) {
      return [
        'voted' => TRUE,
        'uid' => $uid,
        'vote_value' => '0',
      ];
    }
    elseif ($vote_value === 1) {
      return [
        'voted' => TRUE,
        'uid' => $uid,
        'vote_value' => '1',
      ];
    }
    elseif (empty($vote_value)) {
      if ($this->hasThread($entity_id)) {
        return [
          'voted' => TRUE,
          'uid' => $uid,
          'vote_value' => $vote_entity ? (int) $vote_entity->getValue() : 0,
        ];
      }
      return FALSE;
    }
  }

  /**
   * Checks if there is current thread between senator and user about the bill.
   */
  public function hasThread($entity_id) {
    if (!$this->currentUser->isAuthenticated()) {
      return FALSE;
    }
    $user_storage = $this->entityTypeManager->getStorage('user');
    // Need the global user object.
    $user = $user_storage->load($this->currentUser->id());
    $senator = $this->nysUserHelper->getSenator($user);

    $messages = NULL;
    if ($senator) {
      $messages = $this->entityTypeManager->getStorage('private_message')->loadByProperties([
        'field_to' => $senator->field_user_account->target_id ?? [],
        'owner' => $user->id(),
        'field_bill' => $entity_id,
      ]);
    }

    $thread = NULL;
    // @phpstan-ignore-next-line
    if (!empty($messages)) {
      $thread = $this->privateMessageService->getThreadFromMessage(end($messages))->getMessages();
    }
    // @phpstan-ignore-next-line
    return !empty($thread);
  }

}
