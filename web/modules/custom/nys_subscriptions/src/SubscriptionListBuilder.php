<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the subscription entity type.
 */
class SubscriptionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs a new SubscriptionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
          $entity_type,
          $container->get('entity_type.manager')->getStorage($entity_type->id()),
          $container->get('date.formatter'),
          $container->get('redirect.destination')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total subscriptions: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['uid'] = $this->t('Subscriber');
    $header['created'] = $this->t('Created On');
    $header['confirmed'] = $this->t('Confirmed?');
    $header['canceled'] = $this->t('Canceled?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /**
     * @var \Drupal\nys_subscriptions\SubscriptionInterface $entity
     */
    $row['id'] = $entity->toLink();
    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getSubscriber(),
    ];
    // dateFormatter == $container->get('date.formatter')
    $row['created'] = $this->dateFormatter
      ->format($entity->getCreated(), 'custom', 'Y-m-d H:i:s');
    $row['confirmed']['data'] = $entity->get('confirmed')->view(
          [
            'type' => 'boolean',
            'label' => 'hidden',
            'settings' => ['format' => 'unicode-yes-no'],
          ]
      );
    $row['canceled']['data'] = $entity->get('canceled')->view(
          [
            'type' => 'boolean',
            'label' => 'hidden',
            'settings' => ['format' => 'unicode-yes-no'],
          ]
      );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
