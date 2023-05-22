<?php

namespace Drupal\nys_bill_vote\Controller;

use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;
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
   * @param \Drupal\node\Entity\Node $bill_node
   *   The bill on which a vote is being cast.
   * @param mixed $vote_value
   *   The vote value.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The AJAX response.
   *
   * @todo Shouldn't this be a JSON response?
   */
  public function confirmationAjaxCallback(Node $bill_node, mixed $vote_value): Response {
    $vote_result = $this->billVoteHelper->processVote($bill_node, $vote_value);
    $response = $this->t(
      'Vote recorded on node %bill_id: %value',
      ['%bill_id' => $bill_node->id(), '%value' => $vote_value]
    );
    return new Response($response);
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

}
