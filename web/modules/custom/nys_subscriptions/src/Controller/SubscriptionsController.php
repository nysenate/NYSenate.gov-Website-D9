<?php

namespace Drupal\nys_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_subscriptions\SubscriptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

/**
 *
 */
class SubscriptionsController extends ControllerBase {

  /**
   * The subscription entity.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionInterface
   */
  protected $subscription;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a SubscriptionsController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('nys_subscriptions')
    );
  }

  /**
   * Confirm create subscription.
   *
   * @param string $uuid
   *   The UUID of the subscription.
   * @param \Drupal\nys_subscriptions\SubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function confirmCreateSubscription($uuid, SubscriptionInterface $subscription) {
    try {
      $subscriptions = $this->subscription->loadByProperties(['uuid' => $uuid]);

      if (!empty($subscriptions)) {
        $subscription = reset($subscriptions);
        $subscription->confirm();

        // Additional logic after confirming the subscription.
        return new Response('Subscription successfully confirmed.');
      }
      else {
        throw new \Exception('Subscription entity not found for the provided UUID.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new Response('Failed to confirm subscription.');
    }
  }

  /**
   * Remove a subscription.
   *
   * @param string $uuid
   *   The UUID of the subscription.
   * @param \Drupal\nys_subscriptions\SubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function removeSubscription($uuid, SubscriptionInterface $subscription) {
    try {
      $subscriptions = $this->subscription->loadByProperties(['uuid' => $uuid]);

      if (!empty($subscriptions)) {
        $subscription = reset($subscriptions);
        $subscription->cancel();
        return new Response('Subscription successfully removed.');
      }
      else {
        throw new \Exception('Subscription entity not found for the provided UUID.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new Response('Failed to delete subscription.');
    }
  }

  /**
   * Controller method to unsubscribe globally.
   *
   * @param string $uuid
   *   The UUID of the subscription.
   * @param \Drupal\nys_subscriptions\SubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function globalUnsubscribe($uuid, SubscriptionInterface $subscription) {
    try {
      $subscriptions = $this->subscription->loadByProperties(['uuid' => $uuid]);

      if (!empty($subscriptions)) {
        $uids = [];

        foreach ($subscriptions as $subscription) {
          $uids[] = $subscription->getOwnerId();
          $subscription->cancel();
        }

        // Load all subscriptions for the associated UIDs and cancel them.
        $allSubscriptions = $this->subscription->loadByProperties(['uid' => $uids]);
        foreach ($allSubscriptions as $subscription) {
          $subscription->cancel();
        }

        return new Response('Subscriptions successfully canceled.');
      }
      else {
        throw new \Exception('Subscription entity not found for the provided UUID.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new Response('Failed to resolve subscription request.');
    }
  }

}
