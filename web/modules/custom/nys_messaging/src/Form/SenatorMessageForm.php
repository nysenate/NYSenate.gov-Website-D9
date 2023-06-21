<?php

namespace Drupal\nys_messaging\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\nys_users\UsersHelper;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\private_message\Service\PrivateMessageThreadManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for sending private message to a senator.
 */
class SenatorMessageForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Default object for the current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Default object for the current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Default object for messenger serivce.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Default object for current.path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default object for private_message.thread_manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  protected $privateMessageThreadManager;

  /**
   * The constructor for Senator Message Form.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routematch
   *   The current route match object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The path.current object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager object.
   * @param \Drupal\private_message\Service\PrivateMessageThreadManagerInterface $private_message_thread_manager
   *   The private_message.thread_manager object.
   */
  public function __construct(
        CurrentRouteMatch $routematch,
        AccountProxyInterface $current_user,
        MessengerInterface $messenger,
        CurrentPathStack $current_path,
        EntityTypeManagerInterface $entity_type_manager,
        PrivateMessageThreadManagerInterface $private_message_thread_manager
    ) {
    $this->routeMatch = $routematch;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->currentPath = $current_path;
    $this->entityTypeManager = $entity_type_manager;
    $this->privateMessageThreadManager = $private_message_thread_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('current_route_match'),
          $container->get('current_user'),
          $container->get('messenger'),
          $container->get('path.current'),
          $container->get('entity_type.manager'),
          $container->get('private_message.thread_manager'),
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_messaging_senator_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user_id = NULL, $context = NULL, $item_id = NULL) {
    $node = $this->routeMatch->getParameter('node');
    $user_storage = $this->entityTypeManager->getStorage('user');
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $senator = NULL;
    if (!empty($node) && $node->bundle() == 'microsite_page') {
      if ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) {
        $senator = $node->field_senator_multiref->first()->entity;
      }
    }
    elseif (!empty($user_id)) {
      // Get User's District Senator.
      $user = $user_storage->load($user_id);
      $senator = UsersHelper::getSenator($user);
    }

    if (!empty($senator)) {
      if ($senator->hasField('field_user_account') && !$senator->get('field_user_account')->isEmpty()) {
        $senator_user_id = $senator->field_user_account->target_id;
      }
    }

    if (!isset($senator_user_id)) {
      $this->messenger->addError($this->t('We are having trouble locating your senator'));
      return $form;
    }

    if ($this->currentUser->isAnonymous()) {
      $query = [
        'query' => [
          'senator' => $senator->id(),
        ],
      ];
      $url = Url::fromUserInput('/user/register', $query)->toString();
      $response = new RedirectResponse($url);
      $response->send();
      return;
    }

    $form = [];

    $form['title'] = [
      '#markup' => '<h2 class="c-page-header--title">Message Senator</h2>',
      '#weight' => -15,
    ];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $this->t('Senator %title', ['%title' => $senator->label()]),
      '#size' => 50,
      '#disabled' => TRUE,
      '#weight' => -10,
    ];

    $form['recipient_uid'] = [
      '#type' => 'hidden',
      '#value' => $senator_user_id,
      '#weight' => -9,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => '',
      '#weight' => -8,
    ];

    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#rows' => 5,
      '#default_value' => '',
      '#weight' => -7,
    ];

    $form['context'] = [
      '#type'  => 'hidden',
      '#value' => $context ?? 'nys_messaging_senator_message_form',
    ];

    // Set Entity IDs based on context.
    switch ($context) {
      case 'bill_vote':
      case 'following_bill':
        $form['bill_id'] = [
          '#type'  => 'hidden',
          '#value' => $item_id,
        ];
        break;

      case 'issue':
        $form['issue_id'] = [
          '#type'  => 'hidden',
          '#value' => $item_id,
        ];

        $issue = $term_storage->load($item_id);

        $form['subject']['#default_value'] = $issue->label();
        $form['subject']['#disabled'] = TRUE;
        break;

      case 'following_committee':
        $form['committee_id'] = [
          '#type'  => 'hidden',
          '#value' => $item_id,
        ];
        break;

      case 'profile':
        $form['title']['#type'] = 'hidden';
        $form['to']['#type'] = 'hidden';
        break;

      default:
        break;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Message'),
      '#weight' => -6,
    ];

    $form['#attached']['library'][] = 'nys_messaging/nys-messaging';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['subject']) && empty($values['subject'])) {
      $form_state->setErrorByName('subject', $this->t('Subject cannot be left blank'));
    }

    if (isset($values['subject']) && empty($values['message'])) {
      $form_state->setErrorByName('message', $this->t('Message cannot be left blank'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $user_storage = $this->entityTypeManager->getStorage('user');

    $subject = '';
    $message = '';
    if (!empty($values['subject'])) {
      $subject = $values['subject'];
    }

    if (!empty($values['message'])) {
      $message = $values['message'];
    }

    $current_path = $this->currentPath->getPath();
    $current_path = explode('/', $current_path);

    if ($current_path[1] == 'user') {
      // Send message from this person's new message form.
      $user = $user_storage->load($current_path[2]);
    }

    if (!$this->currentUser->id()) {
      $url = Url::fromUserInput('/user/register');
      $response = new RedirectResponse($url);
      $response->send();
      return;
    }

    $message_link = Url::fromUserInput('/user/%userid/dashboard', ['%userid' => $this->currentUser->id()])->toString();
    $dashboard_link = 'dashboard';
    if ($values['recipient_uid'] == 'query') {
      $form_state['redirect'] = [
        $message_link . '/inbox',
        [
          'query' => [],
        ],
      ];

      // Reset counter for debug information.
      $_SESSION['http_request_count'] = 0;
      $_SESSION['bulk_message_filters'] = $_GET;
      $_SESSION['author_uid'] = $this->currentUser->id();

      // Execute the function named batch_example_1 or batch_example_2.
      // @todo This comes from the nys_inbox module.
      // @phpstan-ignore-next-line
      $batch = nys_inbox_bulk_message_by_query();
      batch_set($batch);
      return;
    }

    if (!is_array($values['recipient_uid'])) {
      $values['recipient_uid'] = [$values['recipient_uid']];
    }

    // Create the private message entity.
    $message = PrivateMessage::create(
          [
            'message' => $message,
            'field_subject' => $subject,
          ]
      );
    $message->field_to = $values['recipient_uid'];
    $message->save();

    $recipients = $user_storage->loadMultiple($values['recipient_uid']);
    // Add it to the thread with the senator user.
    $this->privateMessageThreadManager->saveThread(PrivateMessage::load($message->id()), $recipients);

    // Associate the issue to the message while saving.
    if (!empty($values['issue_id'])) {
      $loaded_message = PrivateMessage::load($message->id());
      $loaded_message->field_issue->target_id = $values['issue_id'];
      $loaded_message->save();
    }

    if (empty($_GET['bill_ids']) && !empty($_GET['bill_id'])) {
      $_GET['bill_ids'] = [$_GET['bill_id']];
    }

    // Associate the bill(s) to the message while saving.
    if (isset($_GET['bill_ids'])) {
      $loaded_message = PrivateMessage::load($message->id());
      foreach ($_GET['bill_ids'] as $bill_id) {
        $bills[] = $bill_id;
      }

      if (!empty($bills)) {
        $loaded_message->field_bill = $bills;
        $loaded_message->save();
      }
    }

    // Associate the issue to the message while saving.
    if (isset($_GET['petition_id'])) {
      $loaded_message = PrivateMessage::load($message->id());
      $loaded_message->field_petition->target_id = $_GET['petition_id'];
      $loaded_message->save();
    }

    if (!empty($message->id())) {
      $this->messenger->addStatus($this->t('Your message has been sent!'));
    }

    // Set the right redirect URL based on the context.
    if (empty($_GET['context']) && !empty($values['context'])) {
      $_GET['context'] = $values['context'];
    }

    switch ($_GET['context']) {
      case 'senators_constituents_tab': $redirect_url = $dashboard_link . '/constituents';
        break;

      case 'senators_petitions_tab': $redirect_url = $dashboard_link;
        break;

      case 'senators_questionnaires_tab': $redirect_url = $dashboard_link . '/questionnaires';
        break;

      case 'senators_issues_tab': $redirect_url = $dashboard_link . '/issues';
        break;

      case 'senators_bills_tab': $redirect_url = $dashboard_link . '/bills';
        break;

      case 'bill_vote': $redirect_url = 'node/' . $values['bill_id'];
        break;

      case 'following_bill': $redirect_url = 'node/' . $values['bill_id'];
        break;

      case 'issue': $redirect_url = 'taxonomy/term/' . $values['issue_id'];
        break;

      case 'following_committee': $redirect_url = 'taxonomy/term/' . $values['committee_id'];
        break;

      case 'profile': $redirect_url = $dashboard_link;
        break;

      case 'nys_messaging_senator_message_form':
        $redirect_url = 'node/' . $current_path[2];
        break;

      default: $redirect_url = $dashboard_link . '/inbox';
    }

    $url = Url::fromUserInput('/' . $redirect_url)->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }

}
