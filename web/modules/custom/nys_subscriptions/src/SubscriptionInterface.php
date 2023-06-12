<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a subscription entity type.
 */
interface SubscriptionInterface extends ContentEntityInterface {

  /**
   * Gets the type.
   */
  public function getType(): string;

  /**
   * Gets the subscriber's entity.
   */
  public function getSubscriber(): ?UserInterface;

  /**
   * Gets the subscriber's entity ID.
   */
  public function getSubscriberId(): int;

  /**
   * Gets the creation timestamp.
   */
  public function getCreated(): int;

  /**
   * Gets the confirmation timestamp (zero, if unconfirmed).
   */
  public function getConfirmed(): int;

  /**
   * Gets the cancellation timestamp (zero, if not cancelled).
   */
  public function getCanceled(): int;

  /**
   * Gets the timestamp of the last email to be sent to this subscription.
   */
  public function getLastSent(): int;

  /**
   * Gets the subscription target's full entity.
   */
  public function getTarget(): ?EntityInterface;

  /**
   * Gets the subscription source's full entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The source entity, or NULL if no source is available.
   *   (e.g., the node from which the user created this subscription)
   */
  public function getSource(): ?EntityInterface;

  /**
   * Sets the type.
   */
  public function setType($type): self;

  /**
   * Sets the subscription source from an entity.
   */
  public function setSource(?EntityInterface $entity): self;

  /**
   * Sets the timestamp of the last email to be sent to this subscription.
   */
  public function setLastSent(int $timestamp): self;

  /**
   * Indicates if the subscription has been confirmed.
   */
  public function isConfirmed(): bool;

  /**
   * Indicates if the subscription has been canceled.
   */
  public function isCanceled(): bool;

  /**
   * Confirms the subscription.
   */
  public function confirm();

  /**
   * Cancels the subscription.
   */
  public function cancel();

  /**
   * Sends a confirmation email for this subscription.
   */
  public function sendConfirmationEmail(): array;

}
