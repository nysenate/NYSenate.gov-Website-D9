<?php

namespace Drupal\nys_accumulator\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\nys_accumulator\Event\FirstLoginEvent;
use Drupal\nys_accumulator\Event\SubmitQuestionEvent;
use Drupal\nys_accumulator\Event\UserEditEvent;
use Drupal\nys_accumulator\Event\VoteCastEvent;
use Drupal\nys_accumulator\Events;
use Drupal\nys_accumulator\Service\Accumulator;
use Drupal\nys_accumulator\Service\EventInfoManager;
use Drupal\nys_slack\Service\Slack;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * NYS Accumulator general event subscriber.
 *
 * Each event handler should create an entry, populate $entry->info, and save.
 */
class GeneralEventSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;

  /**
   * NYS Slack messaging service.
   *
   * @var \Drupal\nys_slack\Service\Slack
   */
  protected Slack $slack;

  /**
   * Logging channel for nys_bill_notifications.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityManager;

  /**
   * The Accumulator service.
   *
   * @var \Drupal\nys_accumulator\Service\Accumulator
   */
  protected Accumulator $accumulator;

  /**
   * Accumulator's event info generator plugin manager.
   *
   * @var \Drupal\nys_accumulator\Service\EventInfoManager
   */
  protected EventInfoManager $infoManager;

  /**
   * Constructs event subscriber.
   */
  public function __construct(EventInfoManager $infoManager, Accumulator $accumulator, Slack $slack, EntityTypeManagerInterface $entityManager) {
    $this->accumulator = $accumulator;
    $this->infoManager = $infoManager;
    $this->slack = $slack;
    $this->entityManager = $entityManager;
    $this->logger = $this->getLogger('nys_accumulator');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FlagEvents::ENTITY_FLAGGED => 'flagEntity',
      FlagEvents::ENTITY_UNFLAGGED => 'unflagEntity',
      Events::FIRST_LOGIN => 'firstLogin',
      Events::VOTE_CAST => 'voteCast',
      Events::USER_EDIT => 'userEdit',
      Events::SUBMIT_QUESTION => 'submitQuestion',
    ];
  }

  /**
   * Calls the generator plugin based on $msg_type.
   *
   * @param string $msg_type
   *   An EventInfoGenerator plugin id.
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity to be passed to the generator.
   *
   * @return array
   *   The built event info array, or an empty array on failure.
   */
  protected function buildEventInfo(string $msg_type, EntityInterface $entity): array {

    try {
      /**
       * @var \Drupal\nys_accumulator\EventInfoGeneratorInterface $plugin
       */
      $plugin = $this->infoManager->createInstance($msg_type);
      $event_info = $plugin->build($entity);
    }
    catch (\Throwable $e) {
      $this->logger->error(
            'Failed to build event info', [
              '@msg' => $e->getMessage(),
              '@type' => $msg_type,
              '@entity_type' => $entity->getEntityTypeId() . ':' . $entity->bundle(),
            ]
        );
      $event_info = [];
    }
    return $event_info;

  }

  /**
   * Common wrapper for flag processing.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   A single instance of a flagging.
   * @param bool $is_unflag
   *   TRUE, if flag is being removed instead of set.
   */
  protected function processFlag(FlaggingInterface $flagging, bool $is_unflag = FALSE) {

    // Detect the message type.  Act only on known flags.
    $flag_id = $flagging->getFlagId();
    $msg_type = match ($flag_id) {
      'follow_this_bill' => 'bill',
            'follow_issue' => 'issue',
            'follow_committee', 'follow_group' => 'committee',
            'sign_petition' => 'petition',
            default => '',
    };
    if (!$msg_type) {
      return;
    }

    // Set the message action.
    $msg_action = $flag_id == 'sign_petition' ? 'sign' : 'follow';
    if ($is_unflag) {
      $msg_action = 'un' . $msg_action;
    }

    // Create and save a new accumulator entry.
    $entry = $this->accumulator->createEntry($msg_type, $msg_action, $flagging->getOwner());
    $entry->info = $this->buildEventInfo($msg_type, $flagging->getFlaggable());
    $entry->save();
  }

  /**
   * Acts on an entity being flagged.
   */
  public function flagEntity(FlaggingEvent $event) {
    $this->processFlag($event->getFlagging());
  }

  /**
   * Acts on an entity being unflagged.
   */
  public function unflagEntity(UnflaggingEvent $event) {
    foreach ($event->getFlaggings() as $flagging) {
      $this->processFlag($flagging, TRUE);
    }
  }

  /**
   * Acts on a user's first login.
   */
  public function firstLogin(FirstLoginEvent $event) {
    $this->accumulator
      ->createEntry('account', 'account created', $event->context)
      ->save();
  }

  /**
   * Acts on a vote being cast.  Limited to the bills bundle.
   */
  public function voteCast(VoteCastEvent $event) {
    if ($event->getVotedEntity()->bundle() == 'bill') {
      $entry = $this->accumulator->createEntry(
            'bill',
            $event->context->getValue() ? 'aye' : 'nay',
            $event->context->getOwner()
        );
      $entry->info = $this->buildEventInfo('bill', $event->getVotedEntity());
      $entry->save();
    }
  }

  /**
   * Builds an array of all the fields which trigger the accumulator.
   */
  protected function buildUserArray(User $user): array {
    $ret = ['field_address' => $user->field_address->getValue()];
    $fields_to_compare = [
      'field_first_name' => 'value',
      'field_last_name' => 'value',
      'field_top_issue' => 'target_id',
      'field_dateofbirth' => 'value',
      'field_gender_user' => 'value',
      'field_district' => 'target_id',
      'name' => 'value',
      'mail' => 'value',
      'status' => 'value',
    ];
    foreach ($fields_to_compare as $field => $property) {
      $ret[$field] = $user->$field->$property;
    }
    return $ret;
  }

  /**
   * Acts on a user's profile being created or edited.
   */
  public function userEdit(UserEditEvent $event): void {
    /**
     * @var \Drupal\user\Entity\User $user
     */
    $user = $event->context;
    /**
     * @var \Drupal\user\Entity\User $original
     */
    $original = $user->original ?? NULL;

    // If this account is new (or has not been accessed), register the
    // "created" message and leave.
    if ($user->isNew() || !$user->access->value) {
      $this->accumulator->createEntry('account', 'account created')
        ->setUser($user)
        ->save();
      return;
    }

    // Initialize the flags.
    $need_action = FALSE;
    $district_change = FALSE;

    // Check for "important" changed.  Build the arrays to compare.
    $old = $this->buildUserArray($original);
    $new = $this->buildUserArray($user);

    // Any difference triggers a message.
    if ($new != $old) {
      $need_action = TRUE;
      // If the district is different, trip the second flag.
      if ($new['field_district'] != $old['field_district']) {
        $district_change = TRUE;
      }
    }

    // If important changes were found, generate the appropriate messages.
    if ($need_action) {
      // If moving districts, record the "removed" message in the old district.
      if ($district_change) {
        $old_district_entry = $this->accumulator
          ->createEntry('profile', 'account edited')
          ->setUser($user)
          ->setTarget($original->field_district->entity);
        $old_district_entry->info['status'] = 'removed';
        $old_district_entry->save();
      }

      // Add the "normal" message.  Indicate "added" if district was changed.
      $entry = $this->accumulator
        ->createEntry('profile', 'account edited')
        ->setUser($user)
        ->setTarget($user->field_district->entity);
      if ($district_change) {
        $entry->info['status'] = 'added';
      }
      $entry->save();
    }
  }

  /**
   * Acts on a questionnaire submission.
   */
  public function submitQuestion(SubmitQuestionEvent $event): void {
    /**
     * @var \Drupal\webform\Entity\WebformSubmission $submit
     */
    $submit = $event->context;

    // Only act if this is a new submission.
    if ($submit->isNew()) {
      // Try to find the owning questionnaire (webform) node.
      $form = $submit->getWebform();
      try {
        $nodes = $this->entityManager->getStorage('node')
          ->loadByProperties(
                  [
                    'type' => 'webform',
                    'status' => 1,
                    'webform.target_id' => $form->id(),
                  ]
              );
        $node = current($nodes);
      }
      catch (\Throwable) {
        $node = NULL;
      }

      // If a node could not be found, leave.
      if (!$node) {
        return;
      }

      // Compile the info.
      $info = [
        'form_id' => $node->id(),
        'form_title' => $form->get('title'),
        'stub' => $node->field_title_stub->value,
        'form_values' => [],
      ];
      $fields = $form->getElementsDecoded();
      foreach ($submit->getData() as $field => $value) {
        $info['form_values'][] = [
          'field' => $fields[$field]['#title'] ?? $field,
          'value' => $value,
        ];
      }

      // Create the entry.
      $entry = $this->accumulator->createEntry('petition', 'questionnaire response');
      $entry->info = $info;
      $entry->save();
    }
  }

}
