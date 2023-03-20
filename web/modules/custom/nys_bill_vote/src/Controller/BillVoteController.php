<?php

namespace Drupal\nys_bill_vote\Controller;

use Drupal\Core\Session\AccountProxy;
use Drupal\nys_bill_vote\BillVoteHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Bill Vote Controller class.
 */
class BillVoteController extends ControllerBase {

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
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    BillVoteHelper $bill_vote_helper,
    AccountProxy $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->billVoteHelper = $bill_vote_helper;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nys_bill_vote.bill_vote'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Ajax callback for vote verification.
   *
   * Bill Vote for authenticated users that is performed via url 'intent'
   * provides a mechanism for the user to confirm that the intent is correct.
   * This function allows us to make an ajax to confirm that vote.
   *
   * @param string $entity_id
   *   The entity ID.
   * @param mixed $vote_value
   *   The vote value.
   */
  public function confirmationAjaxCallback($entity_id, $vote_value) {
    return $this->billVoteHelper->processVote('node', $entity_id, $vote_value)['message'];
  }

  /**
   * Ajax callback for auto subscription preferences.
   *
   * For each user, a one-time preference dialog will set the user's preference
   * for auto-subscribing to bills when voting.  This callback handles saving
   * the preference.  It can also be modified via their dashboard.
   *
   * @param string $entity_id
   *   The entity ID.
   * @param mixed $autosub
   *   The autosub value.
   */
  public function autosubAjaxCallback($entity_id, $autosub) {
    $u = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());

    $u->set('field_voting_auto_subscribe', (int) $autosub);

    if ((int) $autosub && (int) $entity_id) {
      // Need to get the current node ID, it's taxonomy ID,
      // and user id and email. The entity_id should be
      // our node ID...look up the tid from there.
      try {
        $node = $this->entityTypeManager->getStorage('node')
          ->load($entity_id);
        $tid = $node->field_bill_multi_session_root->target_id;
      }
      catch (\Exception $e) {
        $tid = 0;
      }

      if ($tid) {
        $data = [
          'email' => $u->mail,
          'tid' => $tid,
          'nid' => $entity_id,
          'uid' => $u->id(),
          'why' => 2,
          'confirmed' => $this->currentUser->isAuthenticated(),
        ];

        // @todo This method comes from nys_subscriptions module.
        // @phpstan-ignore-next-line
        // _real_nys_subscriptions_subscription_signup($data);
      }
    }
    return new Response('Subscribe Complete.');
  }

  /**
   * Sends a private message to a user when voting on a bill.
   *
   * @param array $message
   *   Array with properties for sending a message.
   *
   * @code
   *   // Expects the following array structure.
   *   $message = array(
   *     'senator_nid' => $senator_nid, // Node id for senator's page.
   *     'author' => $user, // Sender.
   *     'vote_label' => $vote_label, // Label for user's vote.
   *     'values' => $values, // Registration form values.
   *     'node' => $node, // Bills node object.
   *   );
   * @endcode
   *
   * @return array
   *   Returns an array of success/error messages.
   */
  public function sendPrivateMessage($message) {
    nyslog();

    // Array for return warnings/errors/success.
    $bills_flag_message = [];
    $values = $message['values'];

    if (empty($message['senator_nid'])) {
      $bills_flag_message[] = t('You must be a NY resident to send messages to a Senator.');
    }
    else {
      // Get the senator's user object.
      $senator = $this->entityTypeManager->getStorage('node')->load($message['senator_nid']);

      // Get the proper language for the vote type.
      switch ($message['vote_label']) {
        case 'Aye':
          $vote_type = 'supported';
          break;

        case 'Nay':
          $vote_type = 'opposed';
          break;

        default:
          $vote_type = 'sent a message regarding';
          break;
      }

      // Create the subject.
      $subject = "{$values['first_name']} {$values['last_name']} {$vote_type} " .
        $message['node']->title;

      // Create the body.
      $body = '';
      if ($values['message']) {
        $body .= "<div style=\"border-left:5px solid #aaaaaa;border-radius:.2em;padding-left:1em\">" .
          filter_xss($values['message']) . "</div>\n\n- - - - - - - - -\n";
      }
      $body .= $message['node']->title;
      if ($sponsor_name = $message['node']->field_ol_sponsor_name[LANGUAGE_NONE][0]['value']) {
        $body .= " - " . $sponsor_name;
      }
      $body .= "\n" .
        field_get_items('node', $message['node'], 'field_ol_name')[0]['value'] .
        "\n" . url($values['pass_thru_url'], array('absolute' => TRUE));

      // Set author and timestamp options.
      $options = array('timestamp' => REQUEST_TIME, 'author' => $message['author']);

      // Send the message.
      $new_msg = privatemsg_new_thread(array($senator), $subject, $body, $options);

      // Set the result message.
      $bills_flag_message[] = ($new_msg['success'] === TRUE)
        ? t('Your message has been sent.')
        : t('There was a problem sending your message.');

      // Sending a context message from the bill vote needs the same follow-up as
      // does sending a new message from the Inbox.  Set the bill_id value to
      // provide context, and make sure the same hook is fired.
      $values['bill_id'] = $message['node']->nid;
      module_invoke_all('nys_inbox_new_message_sent', $values, $new_msg);
    }

    return $bills_flag_message;
  }


}
