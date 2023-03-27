<?php

namespace Drupal\nys_bill_vote;

use Drupal\node\NodeInterface;
use Drupal\votingapi\Entity\Vote;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper class for nys_bill_vote module.
 */
class BillVoteHelper {

  use StringTranslationTrait;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Default object for path.current service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Default object for logger.factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Voting api result function manger.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $voteResultFunctionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AccountProxy $current_user,
    CurrentPathStack $current_path,
    CurrentRouteMatch $current_route_match,
    LoggerChannelFactory $logger,
    EntityTypeManager $entity_type_manager,
    VoteResultFunctionManager $vote_result_function_manager
  ) {
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->currentRouteMatch = $current_route_match;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->voteResultFunctionManager = $vote_result_function_manager;
  }

  /**
   * Helper function to get 'intent' from a Vote text.
   *
   * @param string $vote
   *   The user's vote. Can be 'Aye' or 'Nay'.
   *
   * @return string
   *   The string intent that corresponds to the user's vote. Could be 'support'
   *   or 'oppose'.
   */
  public function getIntentFromVote($vote) {
    $intent = '';
    if ($vote == 'Aye') {
      $intent = 'support';
    }
    elseif ($vote == 'Nay') {
      $intent = 'oppose';
    }

    return $intent;
  }

  /**
   * Translates between vote values and labels.
   *
   * If a value is passed, the appropriate label is returned.
   *
   * If a label is passed, the appropriate value is returned.
   *
   * If the passed value is not found in values or labels,
   * a boolean FALSE is returned.
   *
   * @param int $number
   *   A vote value or label to search for.
   *
   * @return mixed|string
   *   The vote value or label (depending if a label or value, respectively,
   *   was passed).  If passed item is not found, boolean FALSE is returned.
   */
  public function getVal($number) {
    $values = [
      0 => 'no',
      1 => 'yes',
    ];
    if (array_key_exists($number, $values)) {
      $ret = $values[$number];
    }
    elseif (in_array($number, $values)) {
      $ret = array_search($number, $values);
    }
    else {
      $ret = FALSE;
    }
    return $ret;
  }

  /**
   * Basic default options for nys_bill_vote.
   */
  public function getOptions() {

    $options = [
      'yes' => t('Aye'),
      'no' => t('Nay'),
    ];

    return $options;
  }

  /**
   * Clean results.
   *
   * @param array $results
   *   The results array.
   *
   * @return array
   *   The cleaned results array.
   */
  public function cleanResults(array $results) {
    foreach ($results as $key => $value) {
      $results[$value['function']] = $value['value'];
      unset($results[$key]);
    }

    return $results;
  }

  /**
   * Clean votes.
   */
  public function cleanVotes($results, $entity_type, $entity_id) {
    return $this->cleanResults($results[$entity_type][$entity_id]);
  }

  /**
   * Default axis available.
   */
  public function getTags() {
    return ['nys_bill_vote_aye_nay'];
  }

  /**
   * Gets the vote widget label.
   *
   * @param string $value
   *   The existing vote's yes/no value.
   *
   * @return object
   *   The label for the voted option.
   */
  public function getVotedLabel($value = '') {
    // Suss out the label for this rendering.  Default...
    $label = $this->t('Do you support this bill?');

    // If this isn't a bill page, other default...
    $node = $this->currentRouteMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      if (!$node->getType() == 'bill') {
        $label = $this->t("What's your position?");
      }
    }

    // If this is a user examining a bill through their dashboard...
    $current_path = $this->currentPath->getPath();
    $path_args = explode('/', $current_path);
    if ($path_args[2] == 'dashboard' && $path_args[3] == 'bills') {
      $label = $this->t("Do you support this bill?");
    }

    // If an existing vote (including one submitted now) is detected ...
    if ($value == 'yes') {
      $label = $this->t('You are in favor of this bill');
    }
    elseif ($value == 'no') {
      $label = $this->t('You are opposed to this bill');
    }

    return $label;
  }

  /**
   * Process the vote.
   *
   * @param string $entity_type
   *   The type of entity receiving a vote.  Should always be 'bill'.
   * @param int $entity_id
   *   The entity ID (i.e., node number) of the entity receiving a vote.
   * @param int $vote_value
   *   The value of the vote being recorded.
   *
   * @return array|bool
   *   An array of vote result records affected by the vote.
   */
  public function processVote($entity_type, $entity_id, $vote_value) {
    $vote_index = $this->getVal($vote_value);
    $vote_entity = NULL;
    $node = $this->entityTypeManager->getStorage('node')->load($entity_id);

    $message = $this->t('Vote process received value = %vote_value, found index = %vote_index',
      ['%vote_value' => $vote_value, '%vote_index' => $vote_index]
    );
    $this->logger->get('nys_bill_vote')->notice($message);

    $ret = FALSE;
    if ($vote_index !== FALSE) {
      // Check to see if a vote already exists.
      // If the user ID is zero, pass it through.
      if ($this->currentUser->id() === 0) {
        $needs_processing = TRUE;
      }
      else {
        /** @var \Drupal\votingapi\VoteStorage $vote_storage */
        $vote_storage = $this->entityTypeManager->getStorage('vote');
        $user_votes = $vote_storage->getUserVotes($this->currentUser->id(), 'nys_bill_vote', 'node', $entity_id);
        $vote_check = NULL;
        if (!empty($user_votes)) {
          /** @var \Drupal\votingapi\Entity\Vote $vote_entity */
          $vote_entity = $vote_storage->load(end($user_votes));
          $vote_check = (int) $vote_entity->getValue();
        }

        // If no vote exists, or if the vote is different, process the vote.
        $needs_processing = ($vote_check === NULL || ($vote_check !== $vote_index));

        // Also process auto-subscribe if the user has chosen.
        $account = $this->entityTypeManager->getStorage('user')
          ->load($this->currentUser->id());

        // If a subscription was requested, create it.
        if ($account->field_voting_auto_subscribe->value) {
          // Need to get the current node ID, it's taxonomy ID,
          // and user id and email. The entity_id should be
          // our node ID...look up the tid from there.
          try {
            $tid = $node->field_bill_multi_session_root->value;
          }
          catch (\Exception $e) {
            $tid = 0;
          }

          if ($tid && $entity_id) {
            $data = [
              'email' => $account->mail,
              'tid' => $tid,
              'nid' => $entity_id,
              'uid' => $account->id(),
              'why' => 2,
              'confirmed' => $this->currentUser->isAuthenticated(),
            ];

            // @todo This method comes from nys_subscriptions module.
            // @phpstan-ignore-next-line
            // _real_nys_subscriptions_subscription_signup($data);
          }
        }
      }

      if ($needs_processing) {
        // Set the follow flag on this bill for the current user.
        $flag_service = \Drupal::service('flag');
        $flag = $flag_service->getFlagById('follow_this_bill');
        if ($this->currentUser->isAuthenticated()) {
          $current_user = $this->entityTypeManager->getStorage('user')
            ->load($this->currentUser->id());
          if (empty($flag_service->getFlagging($flag, $node, $current_user))) {
            $flag_service->flag($flag, $node, $current_user);
          }
        }

        if (empty($vote_entity)) {
          $vote_entity = Vote::create(['type' => 'nys_bill_vote']);
          $vote_entity->setVotedEntityType('node');
          $vote_entity->setVotedEntityId($entity_id);
          $vote_entity->setValueType('option');
          $vote_entity->setValue($vote_index);
          $vote_entity->setOwnerId($this->currentUser->id());
          if (!$this->currentUser->isAuthenticated()) {
            $vote_entity->setSource(\Drupal::request()->getClientIp());
          }
          $vote_entity->save();
        }
        else {
          /** @var \Drupal\votingapi\Entity\Vote $vote_entity */
          $vote_entity->setValue($vote_index);
          $vote_entity->save();
        }
      }
    }

    return [
      'message' => $message,
      'vote' => $vote_entity,
    ];
  }

  /**
   * Retrieve widget settings.
   *
   * @param object $form_state
   *   A Drupal form state array.
   *
   * @return array|mixed
   *   The settings array.
   */
  public function widgetBuildSettings(object $form_state) {
    // Try to detect the build settings in form_state.
    $build_info = $form_state->getBuildInfo();
    $ret = $build_info['args'][0] ?? [];
    $node_id = NULL;
    $node_type = NULL;

    // If the required info is not found, try to detect it
    // from the current request.
    if (array_diff(['entity_id', 'entity_type'], array_keys($ret))) {
      $node = $this->currentRouteMatch->getParameter('node');
      if ($node instanceof NodeInterface) {
        $node_id = $node->id();
        $node_type = $node->getType();
      }

      // If good info is found, use it.
      if ($node_id && $node_type) {
        $ret = ['entity_id' => $node_id, 'entity_type' => $node_type];
      }
      // Otherwise, set up for a graceful failure.
      else {
        $ret = [];
      }
    }

    return $ret;
  }

  /**
   * Retrieves the default values.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity id.
   *
   * @return mixed
   *   The default values.
   */
  public function getDefault($entity_type, $entity_id) {
    /** @var \Drupal\votingapi\VoteStorage $vote_storage */
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    if ($this->currentUser->id() > 0) {
      $user_votes = $vote_storage->getUserVotes($this->currentUser->id(), 'nys_bill_vote', $entity_type, $entity_id);
    }
    else {
      $user_votes = $vote_storage->getUserVotes($this->currentUser->id(), 'nys_bill_vote', $entity_type, $entity_id, \Drupal::request()->getClientIp());
    }

    if (!empty($user_votes)) {
      /** @var \Drupal\votingapi\Entity\Vote $vote_entity */
      $vote_entity = $vote_storage->load(end($user_votes));
      return $vote_entity->getValue();
    }

    return NULL;
  }

  /**
   * Retrieves the votes.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity id.
   * @param bool $clear
   *   The clear flag.
   *
   * @return array
   *   The votes.
   */
  public function getVotes($entity_type, $entity_id, $clear = FALSE) {
    $entities = &drupal_static(__FUNCTION__, NULL, $clear);
    if (empty($entities[$entity_type][$entity_id])) {
      $results = $this->voteResultFunctionManager->getResults('node', $entity_id);
      $entities[$entity_type][$entity_id] = $results['nys_bill_vote']['vote_sum'];
    }

    return $entities[$entity_type][$entity_id];
  }

}
