<?php

namespace Drupal\nys_bill_vote;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\nys_bills\BillsHelper;
use Drupal\nys_users\UsersHelper;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\VoteResultFunctionManager;

/**
 * Helper class for nys_bill_vote module.
 */
class BillVoteHelper {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  const VOTE_TYPE = 'nys_bill_vote';

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * Default object for path.current service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Voting api result function manger.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected VoteResultFunctionManager $voteResultFunctionManager;

  /**
   * A preconfigured logger channel for 'nys_bill_vote'.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $log;

  /**
   * Drupal's DateTime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * NYS Bills Helper service.
   *
   * @var \Drupal\nys_bills\BillsHelper
   */
  protected BillsHelper $billsHelper;

  /**
   * Flag module Flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected FlagService $flagService;

  /**
   * Constructor.
   */
  public function __construct(
        AccountProxy $current_user,
        TimeInterface $time,
        CurrentPathStack $current_path,
        CurrentRouteMatch $current_route_match,
        LoggerChannelFactory $logger,
        EntityTypeManager $entity_type_manager,
        FlagService $flagService,
        VoteResultFunctionManager $vote_result_function_manager,
        BillsHelper $billsHelper
    ) {

    $this->currentUser = $current_user;
    $this->time = $time;
    $this->currentPath = $current_path;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flagService;
    $this->voteResultFunctionManager = $vote_result_function_manager;
    $this->billsHelper = $billsHelper;

    $this->setLoggerFactory($logger);
    $this->log = $logger->get(self::VOTE_TYPE);
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
    return match ($vote) {
      'Aye' => 'support',
            'Nay' => 'oppose',
            default => '',
    };
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
    if (
      !empty($path_args[2])
      && !empty($path_args[3])
      && $path_args[2] == 'dashboard'
      && $path_args[3] == 'bills'
    ) {
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
   * Processes a vote being cast on a bill.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user casting the vote.
   * @param \Drupal\node\Entity\Node $bill_node
   *   The bill node on which a vote is being cast.
   * @param string $vote_value
   *   The value of the vote being recorded ('yes' or 'no').
   *
   * @return \Drupal\votingapi\Entity\Vote|null
   *   Either the recorded vote, or NULL if it could not be recorded.
   */
  public function processVote(AccountInterface $user, Node $bill_node, string $vote_value): ?Vote {
    // Load the user.
    $user = UsersHelper::resolveUser($user);

    // Translate the vote value into an index.
    $vote_index = $this->getVal($vote_value);

    // For feature tracking purposes.
    $this->log->notice(
          'Received a vote from user %user: %index => %value',
          [
            '%value' => $vote_value,
            '%index' => $vote_index,
            '%user' => $user->id(),
          ]
      );

    // If not logged in, or if vote is not valid, just leave.
    // We no longer allow anonymous voting.
    if ((!$this->currentUser->id()) || ($vote_index === FALSE)) {
      return NULL;
    }

    try {
      /**
       * @var \Drupal\votingapi\VoteStorageInterface $vote_store
       */
      $vote_store = $this->entityTypeManager->getStorage('vote');
    }
    catch (\Throwable) {
      $this->log->error('Failed to instantiate vote storage');
      return NULL;
    }

    $existing_vote = current(
          $vote_store->getUserVotes(
              $user->id(),
              self::VOTE_TYPE,
              $bill_node->getEntityTypeId(),
              $bill_node->id()
          )
      );

    /**
     * @var \Drupal\votingapi\Entity\Vote $vote
     */
    $vote = $existing_vote
        ? $vote_store->load($existing_vote)
        : $vote_store->create(
          [
            'type' => self::VOTE_TYPE,
            'entity_type' => $bill_node->getEntityTypeId(),
            'entity_id' => $bill_node->id(),
            'value' => $vote_index,
            'value_type' => 'option',
            'user_id' => $user->id(),
            'timestamp' => $this->time->getRequestTime(),
          ]
      );

    // If vote is new, or if the vote is different, process the vote.
    $needs_processing = $vote->isNew() || ($vote_index !== $vote->getValue());

    // If the user auto-subscribes when voting, create the subscription.
    // This also creates a flagging entry, for now.
    if ($user->id() && ($user->field_voting_auto_subscribe->value ?? TRUE)) {
      $this->billsHelper->subscribeToBill($bill_node, $user);
    }

    // If needed, set the vote value and save.
    if ($needs_processing) {
      $vote->setValue($vote_index);
      try {
        $vote->save();
      }
      catch (\Throwable $e) {
        $this->log->error(
              'Exception while trying to save a vote',
              [
                '@msg' => $e->getMessage(),
                '@is_new' => $vote->isNew(),
                '@uid' => $vote->getOwnerId(),
                '@target_id' => $vote->getVotedEntityId(),
                '@vote' => $vote->getValue(),
              ]
          );
        return NULL;
      }
    }

    return $vote;
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
    /**
     * @var \Drupal\votingapi\VoteStorage $vote_storage
     */
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    $user_votes = $vote_storage->getUserVotes($this->currentUser->id(), self::VOTE_TYPE, $entity_type, $entity_id);

    if (!empty($user_votes)) {
      /**
       * @var \Drupal\votingapi\Entity\Vote $vote_entity
       */
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
      $entities[$entity_type][$entity_id] = $results[self::VOTE_TYPE]['vote_sum'];
    }

    return $entities[$entity_type][$entity_id];
  }

}
