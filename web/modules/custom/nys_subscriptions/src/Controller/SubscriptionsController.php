<?php

namespace Drupal\nys_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The subscription management action controller.
 */
class SubscriptionsController extends ControllerBase {
  /**
   * The subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SubscriptionsController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $subscriptionStorage
   *   The subscription entity storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityStorageInterface $subscriptionStorage, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->subscriptionStorage = $subscriptionStorage;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager')->getStorage('subscription'),
          $container->get('logger.factory')->get('nys_subscriptions'),
          $container->get('messenger')
      );
  }

  /**
   * Confirm create subscription.
   *
   * @param string $uuid
   *   The UUID of the subscription.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function confirmCreateSubscription($uuid) {
    try {
      /**
       * @var \Drupal\nys_subscriptions\SubscriptionInterface[] $subscriptions
*/
      $subscriptions = $this->subscriptionStorage->loadByProperties(['uuid' => $uuid]);

      if ($subscriptions) {
        $subscription = reset($subscriptions);
        /**
         * @var \Drupal\nys_subscriptions\SubscriptionInterface $subscription
*/
        $subscription->confirm();
        /**
         * @var \Drupal\Core\Entity\EntityStorageInterface $subscribe_from_id
*/
        $subscribe_from_id = $subscription->get('subscribe_from_id')->getValue()[0]['value'];
        // Check if the subscribe_from_id exists and is a valid node.
        if ($subscribe_from_id && $node = Node::load($subscribe_from_id)) {
          $url = $node->toUrl()->toString();
          $this->messenger->addStatus('Subscription successfully confirmed.');
          // Redirect to the page node.
          return new RedirectResponse($url, 302, ['Cache-Control' => 'no-cache']);
        }
        else {
          throw new \Exception('Invalid or missing subscribe_from_id.');
        }
      }
      else {
        throw new \Exception('Subscription entity not found for the provided UUID.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError('Failed to confirm subscription.');
      return new RedirectResponse('/', 302, ['Cache-Control' => 'no-cache']);
    }
  }

  /**
   * Remove a subscription.
   *
   * @param string $uuid
   *   The UUID of the subscription.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function removeSubscription($uuid) {
    try {
      /**
       * @var \Drupal\nys_subscriptions\SubscriptionInterface[] $subscriptions
*/
      $subscriptions = $this->subscriptionStorage->loadByProperties(['uuid' => $uuid]);
      if ($subscriptions) {
        $subscription = reset($subscriptions);
        /**
         * @var \Drupal\nys_subscriptions\SubscriptionInterface $subscription
*/
        $subscription->cancel();
        /**
         * @var \Drupal\Core\Entity\EntityStorageInterface $subscribe_from_id
*/
        $subscribe_from_id = $subscription->get('subscribe_from_id')->getValue()[0]['value'];
        // Check if the subscribe_from_id exists and is a valid node.
        if ($subscribe_from_id && $node = Node::load($subscribe_from_id)) {
          $url = $node->toUrl()->toString();
          $this->messenger->addStatus('Subscription successfully removed.');
          return new RedirectResponse($url, 302, ['Cache-Control' => 'no-cache']);
        }
        else {
          throw new \Exception('Invalid or missing subscribe_from_id.');
        }
      }
      else {
        throw new \Exception('Subscription entity not found for the provided UUID.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError('Failed to confirm subscription.');
      return new RedirectResponse('/', 302, ['Cache-Control' => 'no-cache']);
    }
  }

  /**
   * Global unsubscribe.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the subscription entity is not found.
   */
  public function globalUnsubscribe($uuid) {
    try {
      /**
       * @var \Drupal\Core\Entity\EntityStorageInterface
*/
      $subscriptions = $this->subscriptionStorage->loadByProperties(['uuid' => $uuid]);
      if ($subscriptions) {
        $uids = [];
        foreach ($subscriptions as $subscription) {
          $uids[] = $subscription->get('uid')->target_id;
          /**
           * @var \Drupal\nys_subscriptions\SubscriptionInterface $subscription
*/
          $subscription->cancel();
        }

        // Find other subscriptions with matching UIDs and cancel them.
        $other_subscriptions = $this->subscriptionStorage->loadByProperties(['uid' => $uids]);
        foreach ($other_subscriptions as $subscription) {
          /**
           * @var \Drupal\nys_subscriptions\SubscriptionInterface $subscription
*/
          $subscription->cancel();
        }
        $this->messenger->addStatus('You have successfully globally unsubscribed.');
        return new RedirectResponse('/', 302, ['Cache-Control' => 'no-cache']);
      }
      else {
        throw new NotFoundHttpException();
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError('Failed to confirm global unsubscribe.');
      return new RedirectResponse('/', 302, ['Cache-Control' => 'no-cache']);
    }
  }

}
