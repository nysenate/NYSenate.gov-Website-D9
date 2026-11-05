<?php

namespace Drupal\nys_accumulator\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\nys_accumulator\Event\FirstLoginEvent;
use Drupal\nys_accumulator\Event\SubmitWebformEvent;
use Drupal\nys_accumulator\Event\UserEditEvent;
use Drupal\nys_accumulator\Event\VoteCastEvent;
use Drupal\nys_accumulator\Events;
use Drupal\nys_accumulator\Service\Accumulator;
use Drupal\nys_accumulator\Service\EventInfoManager;
use Drupal\nys_slack\Service\Slack;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\WebformSubmission;
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
      Events::SUBMIT_WEBFORM => 'submitWebform',
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
  protected function processFlag(FlaggingInterface $flagging, bool $is_unflag = FALSE): void {
    // Detect the message type.  Act only on known flags.
    $flag_id = $flagging->getFlagId();
    $msg_type = match ($flag_id) {
      'follow_this_bill' => 'bill',
      'follow_issue' => 'issue',
      'follow_committee', 'follow_group' => 'committee',
      'sign_petition' => 'poll',
      default => '',
    };
    if (!$msg_type) {
      return;
    }

    // Set the message action.
    $msg_action = ($is_unflag ? 'un' : '') . 'follow';

    // Create and save a new accumulator entry.
    $entry = $this->accumulator->createEntry($msg_type, $msg_action, $flagging->getOwner());
    $entry->info = $this->buildEventInfo($msg_type, $flagging->getFlaggable());
    $entry->save();
  }

  /**
   * Acts on an entity being flagged.
   */
  public function flagEntity(FlaggingEvent $event): void {
    $this->processFlag($event->getFlagging());
  }

  /**
   * Acts on an entity being unflagged.
   */
  public function unflagEntity(UnflaggingEvent $event): void {
    foreach ($event->getFlaggings() as $flagging) {
      $this->processFlag($flagging, TRUE);
    }
  }

  /**
   * Acts on a user's first login.
   */
  public function firstLogin(FirstLoginEvent $event): void {
    $this->accumulator
      ->createEntry('account', 'created', $event->context)
      ->save();
  }

  /**
   * Acts on a vote being cast.  Limited to the bills bundle.
   */
  public function voteCast(VoteCastEvent $event): void {
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

    // This event should only be responding to users already assigned
    // a user id.  If it is missing, skip.
    if (!$user->id()) {
      return;
    }

    // If this account is new (i.e., has not been accessed), register the
    // "created" message and leave.
    if (!$user->access->value) {
      $this->accumulator->createEntry('account', 'created')
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
          ->createEntry('account', 'edited')
          ->setUser($user)
          ->setTarget($original->field_district->entity);
        $old_district_entry->info['status'] = 'removed';
        $old_district_entry->save();
      }

      // Add the "normal" message.  Indicate "added" if district was changed.
      $entry = $this->accumulator
        ->createEntry('account', 'edited')
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
  public function submitWebform(SubmitWebformEvent $event): void {
    /** @var \Drupal\webform\Entity\WebformSubmission $submit */
    $submit = $event->context;

    // Only act if this is a new submission.
    if ($submit->isNew()) {
      // Set up the webform's info.
      $info = [
        'form_id' => $submit->getWebform()->id(),
        'form_title' => $submit->getWebform()->get('title'),
        'form_values' => [],
      ];

      // Get the owner.  This also populates 'stub'.
      $owner = $this->getWebformOwner($submit, $info);

      // Get the form field names and values.
      $fields = $submit->getWebform()->getElementsDecoded();
      foreach ($submit->getData() as $field => $value) {
        $info['form_values'][] = [
          'field' => $fields[$field]['#title'] ?? $field,
          'value' => $value,
        ];
      }

      // Create the entry.  The target is the form's owner.
      $entry = $this->accumulator->createEntry('poll', 'webform', NULL, $owner);
      $entry->info = $info;
      $entry->save();
    }
  }

  /**
   * Finds the "owner" of a webform, and populates "stub" in the event info.
   *
   * Some webforms are attached to a senator through a node's field_webform_id,
   * but others appear as blocks with no "owning" node.  For these latter forms,
   * the senator's tid must be injected into the form submission.  This is done
   * in the webform's build source.
   *
   * We also need a "stub" for the form, which serves as a unique identifier.
   * This is auto-populated when creating a new node (field_title_stub).  For
   * the blocks, we create the stub from the senator's name and the form id.
   *
   * Any errors in loading the nodes/terms or in accessing their fields will
   * result in a NULL owner.  The stub will default to the form's title or id.
   *
   * See senator_contact_form webform config for how to configure tid.
   * See nys_petitions_handle_stub_form_validate() for how stubs are made.
   */
  protected function getWebformOwner(WebformSubmission $submission, array &$info): ?ContentEntityInterface {
    $form = $submission->getWebform();
    // Detect if the form is "owned" by a node.
    try {
      $nodes = $this->entityManager->getStorage('node')
        ->loadByProperties([
          'type' => 'webform',
          'status' => 1,
          'webform.target_id' => $form->id(),
        ]);
      $node = current($nodes);
      $owner = $this->entityManager->getStorage('taxonomy_term')
        ->load($node->field_senator_multiref->value);
      assert($owner instanceof Term);
      $stub = $node->field_title_stub?->value ?: $node->label();
    }
    catch (\Throwable) {
      $stub = '';
      $owner = NULL;
    }

    // That didn't work.  See if a senator was injected into 'tid'.
    // The stub will be the form id plus the owner's name.
    if (!$stub) {
      $tid = $submission->getElementData('tid');
      if ($tid) {
        try {
          $owner = $this->entityManager->getStorage('taxonomy_term')
            ->load($tid);
          assert($owner instanceof Term);
          $stub = $form->id() . ' ' . $owner->label();
        }
        catch (\Throwable) {
          $stub = '';
          $owner = NULL;
        }
      }
    }

    // If a stub has been found, use it.  Otherwise, use the form name/id.
    // Enforce the same length/content restrictions.
    $stub = $stub ?: (
      $submission->getWebform()?->get('title') ?? $info['form_id']
    );
    $info['stub'] = substr(
      preg_replace('/[^0-9a-z_]/', '-', strtolower($stub)),
      0, 115);

    return $owner;
  }

}
