<?php

namespace Drupal\nys_messaging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The constructor for Senator Message Form.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routematch
   *   The current route match object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Messenger\MessengerInterface
   *   The messenger object.
   */
  public function __construct(CurrentRouteMatch $routematch, AccountProxyInterface $current_user, MessengerInterface $messenger) {
    $this->routeMatch = $routematch;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('messenger'),
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->routeMatch->getParameter('node');

    $senator = NULL;
    if (!empty($node) && $node->bundle() == 'microsite_page') {
      if ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) {
        $senator = $node->field_senator_multiref->first()->entity;
      }
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

    if (!$this->currentUser->isAuthenticated()) {
      $query = [
        'query' => [
          'senator' => $senator->id(),
        ],
      ];
      $url = Url::fromUserInput('registration/nojs/form/start/message-senator', $query);
      $response = new RedirectResponse();
    }

    $form = [];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $this->t('Senator %title', ['%title' => $senator->label()]),
      '#weight' => -10,
      '#size' => 50,
      '#disabled' => TRUE,
      '#weight' => -10,
    ];
  
    $form['recipient_uid'] = [
      '#type' => 'hidden',
      '#value' => $senator_user_id,
      '#weight' => -9
    ];
    
    $form['context'] = [
      '#type'  => 'hidden',
      '#value' => 'nys_messaging_senator_message_form',
    ];
    
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => '',
      '#weight' => -8
    ];
    
    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#rows' => 5,
      '#default_value' => '',
      '#weight'=> -7
    ];
    
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Message'),
      '#weight' => -6,
    ];
  
    $form['#submit'] = ['nys_inbox_message_form_submit'];
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

    if (!empty($values['message']) && empty($values['body']['value'])) {
      $values['body']['value'] = $values['message'];
    }
  
  
    if(arg(0) == 'user') {
      $user = user_load(arg(1)); // send message from this person's new message form
    }
    else {
      // This is looking for "message senator" form.  May not need to be specific.
      //if(arg(0) == 'node' && arg(2) == 'message')
      global $user;
    }
  
    if (!$user->uid) {
      drupal_goto('/registration/nojs/form/start/message-senator');
    }
  
    $dashboard_link = substr(url('user/' . $user->uid . '/dashboard'),1);
  
    if ($values['recipient_uid'] == 'query') {
  
      $form_state['redirect'] = array(
        $dashboard_link . '/inbox',
        array(
          'query' => array()
        ),
      );
  
  
      $_SESSION['http_request_count'] = 0; // reset counter for debug information.
  
      $_SESSION['bulk_message_filters'] = $_GET;
      $_SESSION['author_uid'] = $user->uid;
  
      // Execute the function named batch_example_1 or batch_example_2.
      $batch = nys_inbox_bulk_message_by_query();
      batch_set($batch);
      return;
  
    }
  
    if (!is_array($values['recipient_uid'])) {
      $values['recipient_uid'] = array($values['recipient_uid']);
    }
    $recipients = user_load_multiple($values['recipient_uid']);
  
    $options = array(
      'author' => $user,
    );
  
  
  
    $message = privatemsg_new_thread($recipients, $values['subject'], $values['body']['value'], $options);
  
    // Associate the issue to the message while saving
    if(!empty($values['issue_id'])) {
  
      $mid = $message['message']->mid;
      $loaded_message = privatemsg_message_load($mid);
      $loaded_message->field_issues['und'][]['tid'] = $values['issue_id'];
      field_attach_update('privatemsg_message', $loaded_message);
    }
  
  
    if(empty($_GET['bill_ids']) && !empty($_GET['bill_id'])) {
      $_GET['bill_ids'] = array($_GET['bill_id']);
    }
  
    // Associate the bill(s) to the message while saving
    if(isset($_GET['bill_ids'])) {
      $mid = $message['message']->mid;
      $loaded_message = privatemsg_message_load($mid);
      foreach($_GET['bill_ids'] as $bill_id) {
        $loaded_message->field_featured_bill['und'][]['target_id'] = $bill_id;
      field_attach_update('privatemsg_message', $loaded_message);
      }
    }
  
    // Associate the issue to the message while saving
    if(isset($_GET['petition_id'])) {
      $mid = $message['message']->mid;
      $loaded_message = privatemsg_message_load($mid);
  
      $loaded_message->field_petitions_questionnaires['und'][]['target_id'] = $_GET['petition_id'];
      field_attach_update('privatemsg_message', $loaded_message);
    }
  
    if (isset($message) && $message['success'] == 1) {
      drupal_set_message(t("Your message has been sent!"), 'status');
    }
  
    // Set the right redirect URL based on the context
  
    if(empty($_GET['context']) && !empty($values['context'])) {
      $_GET['context'] = $values['context'];
    }
  
    switch($_GET['context']) {
      case 'senators_constituents_tab' : $redirect_url = $dashboard_link . '/constituents'; break;
      case 'senators_petitions_tab' : $redirect_url = $dashboard_link; break;
      case 'senators_questionnaires_tab' : $redirect_url = $dashboard_link . '/questionnaires'; break;
      case 'senators_issues_tab' : $redirect_url = $dashboard_link . '/issues'; break;
      case 'senators_bills_tab' : $redirect_url = $dashboard_link . '/bills'; break;
      case 'bill_vote' : $redirect_url = 'node/' . $values['bill_id']; break;
      case 'following_bill' : $redirect_url = 'node/' . $values['bill_id']; break;
      case 'issue' : $redirect_url = 'taxonomy/term/' . $values['issue_id']; break;
      case 'following_committee' : $redirect_url = 'taxonomy/term/' . $values['committee_id']; break;
      case 'nys_messaging_senator_message_form' :
        $redirect_url = 'node/' . arg(1);
      break;
      default: $redirect_url = $dashboard_link . '/inbox';
    }
  
    $form_state['redirect'] = array(
      $redirect_url,
      array(
        'query' => array()
      ),
    );
  
    foreach (module_implements('nys_inbox_new_message_sent') as $module) {
      $function = $module . '_nys_inbox_new_message_sent';
      // will call all modules implementing hook_hook_name
      // and can pass each argument as reference determined
      // by the function declaration
      $function($values, $message);
    }
  }

}
