<?php

namespace Drupal\nys_subscriptions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\nys_subscriptions\Event\GetSubscribersEvent;
use Drupal\nys_subscriptions\Events;
use Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity;
use Drupal\nys_subscriptions\Subscriber;
use Drupal\nys_subscriptions\SubscriptionInterface;
use Drupal\nys_subscriptions\SubscriptionQueue;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the subscription entity class.
 *
 * @ContentEntityType(
 *   id = "subscription",
 *   label = @Translation("Subscription"),
 *   label_collection = @Translation("Subscriptions"),
 *   handlers = {
 *     "storage_schema" = "Drupal\nys_subscriptions\SubscriptionStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nys_subscriptions\SubscriptionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "nys_subscriptions",
 *   admin_permission = "administer subscriptions",
 *   entity_keys = {
 *     "id" = "sub_id",
 *     "label" = "sub_id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/subscriptions/{subscription}",
 *     "delete-form" = "/admin/content/subscriptions/{subscription}/delete",
 *     "collection" = "/admin/content/subscriptions",
 *     "confirm" = "/subscriptions/subscribe/{uuid}",
 *     "unsubscribe" = "/subscriptions/unsubscribe/{uuid}",
 *     "global-unsubscribe" = "/subscriptions/global-unsubscribe/{user_uuid}"
 *   },
 *   field_ui_base_route = "entity.subscription.settings",
 *   constraints = {
 *     "SubscriptionSubscribeTo" = {}
 *   }
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  use LoggerChannelTrait;

  const SUBSCRIBERS_ALL = 0;

  const SUBSCRIBERS_ACTIVE_ONLY = 1;

  const SUBSCRIBERS_UNCONFIRMED_ONLY = 2;

  const SUBSCRIBERS_CANCELED_ONLY = 4;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity described by the subscription's `subscribe_to_*` fields.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $target = NULL;

  /**
   * The entity described by the subscription's `subscribe_from_*` fields.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $source = NULL;

  /**
   * Drupal's Event Dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Drupal's Mail Manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailer;

  /**
   * Drupal's Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $language;

  /**
   * {@inheritDoc}
   *
   * When creating a subscription:
   *  - passing an entity in $values['target'] will override any passed values
   *    for `subscribe_to_type` and `subscribe_to_id`.  Either 'target' must be
   *    a valid entity, or type and id must allow one to be loaded.
   *  - passing an entity in $values['source'] will override any passed values
   *    for `subscribe_from_type` and `subscribe_from_id`.  If a valid entity
   *    can not be found from either 'source' or the `subscribe_from_*` values,
   *    the fields will be set to NULL.
   *  - passing a valid user ID in $values['uid'] will ensure the `email`
   *    field is set to that user's email of record.  If 'uid' is not passed
   *    at all (i.e., the key is not present), then the current session's
   *    user will be used instead.  If 'uid' is explicitly set to zero, then
   *    a subscription for an unauthenticated user will be created, and
   *    $values['email'] must be populated with a valid email address.
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->dispatcher = \Drupal::service('event_dispatcher');
    $this->mailer = \Drupal::service('plugin.manager.mail');
    $this->language = \Drupal::service('language_manager');
  }

  /**
   * Resolves the entity vs type/id debate in the constructor's $values.
   *
   * Any exceptions coming from the manager or storage are ignored, resulting
   * in a NULL return.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A pre-loaded entity.
   * @param string $type
   *   The type of entity.
   * @param int $id
   *   An entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the passed entity, if available, or an entity loaded based on the
   *   passed type and id.  If an entity could not be loaded, return NULL.
   */
  protected function resolveEntity(?EntityInterface $entity, string $type = '', int $id = 0): ?EntityInterface {
    if (!($entity instanceof EntityInterface)) {
      try {
        $entity = $this->entityTypeManager->getStorage($type)->load($id);
      }
      catch (\Throwable) {
        $entity = NULL;
      }
    }
    return $entity;
  }

  /**
   * Resolves the target entity.
   *
   * If no target entity is already assigned, decides between an entity passed
   * during creation (preferred) and loading an entity based on the values of
   * the `subscribe_to_*` fields.  An exception is thrown if the result is not
   * a valid entity.
   *
   * If $refresh is true, that process is executed even if a target is already
   * assigned.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  protected function resolveTarget(bool $refresh = FALSE): EntityInterface {
    if (($this->target instanceof EntityInterface) && !$refresh) {
      $target = $this->target;
    }
    else {
      // Resolve the target entity.
      $target = $this->resolveEntity(
        $this->values['target'] ?? NULL,
        (string) $this->get('subscribe_to_type')->value,
        (int) $this->get('subscribe_to_id')->value
      );
    }

    // If a target entity could not be loaded, stop here.
    if (is_null($target)) {
      throw new InvalidSubscriptionEntity('Could not resolve subscription target');
    }

    return $target;
  }

  /**
   * Loads the target and source entities based on the field values.
   *
   * If 'source' or 'target' were passed during creation, they can be found
   * in the `values` property.
   *
   * If the target cannot be resolved, an exception is thrown.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  protected function initEntities($refresh = FALSE) {
    // If a refresh is requested, clear any target/source passed during create.
    if ($refresh) {
      unset($this->values['target']);
      unset($this->values['source']);
    }

    // Throws an exception if a target entity cannot be resolved.
    $this->setTarget($this->resolveTarget($refresh));

    if ($refresh || (!isset($this->source))) {
      $this->setSource(
        $this->resolveEntity(
          $this->values['source'] ?? NULL,
          (string) $this->get('subscribe_from_type')->value,
          (int) $this->get('subscribe_from_id')->value
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   *
   * When a new subscription entity is created without specifying a user, sets
   * the uid entity reference and the email to the current user.
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Set the subscriber to the current user if a uid was not specified.
    if (!isset($values['uid'])) {
      $user = \Drupal::currentUser();
      $values['uid'] = $user->id();
      if ($values['uid']) {
        $values['email'] = $user->getEmail();
      }
    }
  }

  /**
   * {@inheritDoc}
   *
   * Ensures source and target entities are loaded.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);
    $this->initEntities();
  }

  /**
   * {@inheritDoc}
   *
   * Ensures source and target entities are loaded.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);

    /** @var static $one_sub */
    foreach ($entities as $one_sub) {
      $one_sub->initEntities(TRUE);
    }
  }

  /**
   * {@inheritDoc}
   *
   * Ensures:
   *  - if the user is authenticated, the user's account email is used,
   *  - the creation date is set when saving a new instance,
   *  - the entity's validation is run.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   *
   * @see \Drupal\nys_subscriptions\Plugin\Validation\Constraint\TypeNotValidConstraintValidator
   * @see \Drupal\nys_subscriptions\Plugin\Validation\Constraint\SubscribeToConstraintValidator
   */
  public function preSave(EntityStorageInterface $storage) {
    // Ensure the email is set from the user, if one is specified.
    if (($user = $this->getSubscriber()) && ($user->id())) {
      $this->set('email', $user->getEmail());
    }

    // Ensure the created timestamp is populated.
    $this->setCreated();

    // Enforce validation.
    $violations = $this->validate();
    if ($violations->count()) {
      // @todo Add all the messages?
      throw new InvalidSubscriptionEntity($violations->get(0)->getMessage());
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritDoc}
   */
  public function getTarget(): ?EntityInterface {
    return $this->target;
  }

  /**
   * Sets the type and ID for the subscription's target.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  protected function setTarget(EntityInterface $entity): self {
    // If the id and type are not populated, stop here.
    if (!($entity->id() && $entity->getEntityTypeId())) {
      throw new InvalidSubscriptionEntity('Invalid entity used as subscription target');
    }

    $this->target = $entity;
    $this->set('subscribe_to_id', $entity->id())
      ->set('subscribe_to_type', $entity->getEntityTypeId());
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getSource(): ?EntityInterface {
    return $this->source;
  }

  /**
   * {@inheritDoc}
   */
  public function setSource(?EntityInterface $entity): self {
    if (($entity instanceof EntityInterface) && $entity->id() && $entity->getEntityTypeId()) {
      $this->set('subscribe_from_type', $entity->getEntityTypeId())
        ->set('subscribe_from_id', $entity->id());
      $this->source = $entity;
    }
    else {
      $this->set('subscribe_from_type', NULL)
        ->set('subscribe_from_id', NULL);
      $this->source = NULL;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated(): int {
    return $this->get('created')->value ?? 0;
  }

  /**
   * Sets the creation date, which is always right now if not already set.
   */
  protected function setCreated(): self {
    if (!$this->getCreated()) {
      $this->set('created', time());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriber(): ?UserInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriberId(): int {
    return $this->get('uid')->target_id ?? 0;
  }

  /**
   * Sets the subscriber by user ID.  Pass zero for unauthenticated.
   */
  protected function setSubscriberId($uid): self {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * Sets the subscriber by passed entity.
   */
  public function setSubscriber(UserInterface $account): self {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSent(): int {
    return $this->get('last_sent')->value ?? 0;
  }

  /**
   * {@inheritDoc}
   */
  public function setLastSent(int $timestamp): self {
    $this->set('last_sent', $timestamp);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getType(): string {
    return $this->get('sub_type')->value ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function getConfirmed(): int {
    return $this->get('confirmed')->value ?? 0;
  }

  /**
   * {@inheritDoc}
   */
  public function getCanceled(): int {
    return $this->get('canceled')->value ?? 0;
  }

  /**
   * {@inheritDoc}
   */
  public function setType($type): self {
    $this->set('sub_type', $type);
    return $this;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function confirm(): self {
    if (!$this->getConfirmed()) {
      $this->set('confirmed', time());
      if ($this->get('sub_id')->value) {
        $this->save();
      }
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cancel(): self {
    if (!$this->getCanceled()) {
      $this->set('canceled', time());
      if ($this->get('sub_id')->value) {
        $this->save();
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfirmed(): bool {
    return (bool) $this->getConfirmed();
  }

  /**
   * {@inheritdoc}
   */
  public function isCanceled(): bool {
    return (bool) $this->getCanceled();
  }

  /**
   * Sends a confirmation email.
   *
   * Note this does not check for confirmation before sending.
   *
   * @return array
   *   The message array.
   */
  public function sendConfirmationEmail(): array {
    return $this->mailer->mail(
      'nys_subscriptions_confirmation',
      'confirmation',
      '',
      $this->language->getCurrentLanguage()->getId(),
      ['subscription' => $this]
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function urlRouteParameters($rel): array {
    $ret = parent::urlRouteParameters($rel);
    $ret['uuid'] = $this->uuid();
    $ret['user_uuid'] = $this->getSubscriber()->uuid();
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sub_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscription Type'))
      ->setDescription(t('The type of subscription.'))
      ->setSetting('is_ascii', TRUE)
      ->setDisplayOptions('view', ['weight' => 0])
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('SubscriptionTypeNotValid');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subscriber ID'))
      ->setDescription(t('The user ID of the subscriber, if attached to an account.'))
      ->setSettings(['target_type' => 'user'])
      ->setDefaultValue(0)
      ->setDisplayOptions('view', ['weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email Address'))
      ->setDescription(t("The subscriber's email address."))
      ->setDisplayOptions('view', ['weight' => 0, 'type' => 'email_mailto'])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['subscribe_to_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscribed To'))
      ->setDescription(t('The entity type being subscribed to.'))
      ->setDisplayOptions('view', ['weight' => 0])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['subscribe_to_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Subscribed To ID'))
      ->setDescription(t('The ID of the entity being subscribed to.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', ['type' => 'number_integer', 'weight' => 0])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['subscribe_from_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscribed From'))
      ->setDescription(t('The entity type which generated the subscription.'))
      ->setDisplayOptions('view', ['weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscribe_from_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Subscribed From ID'))
      ->setDescription(t('The ID of the entity which generated the subscription.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', ['type' => 'number_integer', 'weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time the subscription was created.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'settings' => ['date_format' => 'short'],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Confirmed'))
      ->setDescription(t('The time the subscription was confirmed.'))
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'settings' => ['format' => 'yes-no'],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['canceled'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Canceled'))
      ->setDescription(t('The time the subscription was canceled.'))
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'settings' => ['format' => 'yes-no'],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_sent'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Mail'))
      ->setDescription(t('The time of the last mail sent on behalf of this entry.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'settings' => ['date_format' => 'short'],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets all subscribers for an entity, specified by type and id.
   *
   * @param string $type
   *   The entity type of the subscription target.
   * @param string $id
   *   The id of the subscription target.
   * @param int $flags
   *   A bit-flag value indicating a selection type.  See the class constants.
   *
   * @return array
   *   An array of Subscriber objects for the entity identified by $type and
   *   $id, filtered by the passed $flag value, and chunked by the configured
   *   maximum recipients (defaults to 1000).  If any error occurs, an empty
   *   array is returned.
   */
  public static function getSubscribers(string $type, string $id, int $flags = self::SUBSCRIBERS_ACTIVE_ONLY): array {
    try {
      $entity = \Drupal::entityTypeManager()
        ->getStorage($type)
        ->load($id);

      // Have to do our own query, since loadByProperties does only allows
      // tests "=" and "IN".
      $sub_storage = \Drupal::entityTypeManager()->getStorage('subscription');
      $subs = $sub_storage->getQuery()
        ->condition('subscribe_to_type', $type)
        ->condition('subscribe_to_id', $id);

      // Handle the flags.
      switch ($flags) {
        case static::SUBSCRIBERS_ACTIVE_ONLY:
          $group = $subs->orConditionGroup()
            ->notExists('canceled')
            ->condition('canceled', '0');
          $subs->condition('confirmed', 0, '>')->condition($group);
          break;

        case static::SUBSCRIBERS_UNCONFIRMED_ONLY:
          $group = $subs->orConditionGroup()
            ->notExists('confirmed')
            ->condition('confirmed', '0');
          $subs->condition($group);
          break;

        case static::SUBSCRIBERS_CANCELED_ONLY:
          $subs->condition('canceled', 0, '>');
          break;
      }

      // Execute and load all the entities.
      $subscriptions = $sub_storage->loadMultiple($subs->execute());
    }
    catch (\Throwable) {
      $subscriptions = [];
      $entity = NULL;
    }

    // Create the subscriber objects from the results.
    $ret = [];
    foreach ($subscriptions as $subscriber) {
      try {
        $ret[] = Subscriber::createFromSubscription($subscriber);
      }
      catch (\Throwable $e) {
        \Drupal::logger('nys_subscriptions')
          ->error("Failed to generate subscriber for subscription @id", [
            '@id' => $subscriber->id(),
            '@message' => $e->getMessage(),
          ]);
      }
    }

    // Allow for other code to alter the found subscribers.
    \Drupal::service('event_dispatcher')
      ->dispatch(new GetSubscribersEvent($entity, $ret, $flags), Events::GET_SUBSCRIBERS);

    // Chunk the results and return.
    $max = \Drupal::config('nys_subscriptions.settings')->get('max_recipients');
    return array_chunk($ret, $max ?? SubscriptionQueue::MAX_RECIPIENTS_DEFAULT);
  }

}
