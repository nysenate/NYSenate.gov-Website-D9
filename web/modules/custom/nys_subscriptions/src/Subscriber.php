<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\nys_subscriptions\Entity\Subscription;
use Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity;

/**
 * A business-level representation of an active Subscription entity.
 *
 * This is mostly a wrapper class to allow for ad hoc subscribers while events
 * are processed.  It should exist only in the context of a subscription queue
 * item, which is the result of a subscription event being triggered.
 *
 * This class is purposefully light-weight to avoid bloat and other issues when
 * serializing it for storage in a queue.  Avoid any injections establishing
 * local properties to hold complicated objects.
 *
 * All instances will report being confirmed and not canceled.  For instances
 * created from an array of values, the user ID will default to zero, but a
 * passed value for 'uid' will be respected.  All other properties receive:
 *   - sub_id = 0
 *   - uuid = '00000000-0000-0000-0000-000000000000'
 *   - subscribe_from_type = NULL
 *   - subscribe_from_id = NULL
 *   - created = time()
 *   - last_sent = 0
 */
class Subscriber {

  /**
   * The Subscription entity ID.
   *
   * @var int
   */
  protected int $subId;

  /**
   * A UUID string.
   *
   * @var string
   */
  protected string $uuid;

  /**
   * The queue identifier.
   *
   * @var string
   */
  protected string $subType;

  /**
   * The subscriber's user id, if applicable.
   *
   * @var int
   */
  protected int $uid;

  /**
   * The email address.
   *
   * @var string
   */
  protected string $email;

  /**
   * The entity type ID of the entity to which this subscription belongs.
   *
   * @var string
   */
  protected string $subscribeToType;

  /**
   * The entity ID of the entity to which this subscription belongs.
   *
   * @var int
   */
  protected int $subscribeToId;

  /**
   * The entity type ID of the subscription source.
   *
   * @var string|null
   */
  protected ?string $subscribeFromType;

  /**
   * The entity ID of the subscription source.
   *
   * @var int|null
   */
  protected ?int $subscribeFromId;

  /**
   * The created timestamp.
   *
   * @var int
   */
  protected int $created;

  /**
   * The timestamp of the last email received by this subscription.
   *
   * @var int
   */
  protected int $lastSent;

  /**
   * Constructor.
   *
   * All parameters reflect Subscription entity fields.  The confirmed and
   * canceled fields are omitted.
   */
  protected function __construct(
        int $sub_id,
        string $uuid,
        string $sub_type,
        int $uid,
        string $email,
        string $subscribe_to_type,
        int $subscribe_to_id,
        ?string $subscribe_from_type,
        ?int $subscribe_from_id,
        int $created,
        int $last_sent
    ) {
    $this->subId = $sub_id;
    $this->uuid = $uuid;
    $this->subType = $sub_type;
    $this->uid = $uid;
    $this->email = $email;
    $this->subscribeToType = $subscribe_to_type;
    $this->subscribeToId = $subscribe_to_id;
    $this->subscribeFromType = $subscribe_from_type;
    $this->subscribeFromId = $subscribe_from_id;
    $this->created = $created;
    $this->lastSent = $last_sent;
  }

  /**
   * Wrapper around createFromSubscription() to create from a passed ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   */
  public static function createFromLoad(int $id): Subscriber {
    /**
* @var \Drupal\nys_subscriptions\Entity\Subscription $sub
*/
    $sub = \Drupal::entityTypeManager()->getStorage('subscription')->load($id);
    return static::createFromSubscription($sub);
  }

  /**
   * Create a subscriber object from a Subscription entity.
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   *   If the passed subscription is canceled or unconfirmed.
   */
  public static function createFromSubscription(Subscription $sub): Subscriber {
    if ($sub->getCanceled() || !$sub->isConfirmed()) {
      throw new InvalidSubscriptionEntity('A subscriber cannot be created from a canceled or unconfirmed subscription');
    }
    return new static(
          $sub->id() ?? 0,
          $sub->uuid() ?? '',
          $sub->sub_type->value ?? '',
          $sub->uid->target_id ?? 0,
          $sub->email->value ?? '',
          $sub->subscribe_to_type->value ?? '',
          $sub->subscribe_to_id->value ?? 0,
          $sub->subscribe_from_type->value ?? '',
          $sub->subscribe_from_id->value ?? 0,
          $sub->created->value ?? 0,
          $sub->last_sent->value ?? 0
      );
  }

  /**
   * Creates a subscriber object from an array of values.
   *
   * @param array $values
   *   Must include values for 'sub_type' (queue name), 'email',
   *   'subscribe_to_type', and 'subscribe_to_id'.
   *
   * @return static
   *
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   *   If the passed array does not include the four necessary values.
   */
  public static function createFromValues(array $values): Subscriber {
    if (!(($values['sub_type'] ?? FALSE)
          && ($values['email'] ?? FALSE)
          && ($values['subscribe_to_type'] ?? FALSE)
          && ($values['subscribe_to_id'] ?? FALSE))
      ) {
      throw new InvalidSubscriptionEntity('A subscriber must have a type (sub_type), email, and target identifiers (subscribe_to_type and subscribe_to_id)');
    }
    $arr = [
      0,
      '00000000-0000-0000-0000-000000000000',
      $values['sub_type'],
      $values['uid'] ?? 0,
      $values['email'],
      $values['subscribe_to_type'],
      $values['subscribe_to_id'],
      '',
      0,
      time(),
      0,
    ];
    return new static(...$arr);
  }

  /**
   * Magic getter to expose protected properties.
   */
  public function __get($name) {
    return match ($name) {
      'confirmed' => time(),
            'canceled' => 0,
            default => $this->{$name},
    };
  }

  /**
   * Wrapper, just to make PHPStorm feel better.
   */
  public function get($name): mixed {
    return $this->__get($name);
  }

  /**
   * Retrieves the subscription's target entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTargetEntity(): ?EntityInterface {
    return \Drupal::entityTypeManager()
      ->getStorage($this->subscribeToType)
      ->load($this->subscribeToId);
  }

  /**
   * Formats the "created" timestamp.
   */
  public function formatCreated(string $format = 'long'): string {
    // Create a date object from the 'created' epoch timestamp.
    $datetime = date_create_from_format('U', $this->created);
    // Suss the desired format.
    $format = match ($format) {
      'long' => 'l F jS, Y',
            default => $format
    };

    return $datetime
        ? $datetime->format($format)
        : '[No date available]';
  }

}
