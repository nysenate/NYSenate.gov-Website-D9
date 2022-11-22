<?php

namespace Drupal\nys_sunset_policy\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Custom Queue Worker.
*
* @QueueWorker(
*   id = "nys_sunset_expired_queue",
*   title = @Translation("Sunset Policy Expired Queue"),
*   cron = {"time" = 5}
* )
*/
final class SunsetExpiredQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Main constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Used to grab functionality from the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
     $configuration,
     $plugin_id,
     $plugin_definition,
     $container->get('entity_type.manager'),
     $container->get('database'),
    );
  }

  /**
   * Processes an item in the queue.
   *
   * @param mixed $data
   *   The queue item data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function processItem($data) {
    $nid = $data->nid;

    // Processing of queue items logic goes here.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $subject = "Content will expire soon - " . $node->getTitle();
    $mailManager = \Drupal::service('plugin.manager.mail');
    $expire_date = date('l M jS Y', strtotime($node->field_expiration_date->getValue()[0]['value']));
    $subject = "This item is set to be unpublished from the New York Senate web site on " . $expire_date;
    $mailManager = \Drupal::service('plugin.manager.mail');
    $senator_terms = $node->get('field_senator_multiref')->referencedEntities();
    $senator_email = '';
    foreach ($senator_terms as $senator_term) {
      $senator_email = $senator_term->get('field_email')->getValue()[0]['value'];
    }
    $params['message'] = '';
    $params['title'] = $subject;
    $params = ['subject' => $subject, 'body' => $body];
    $result = $mailManager->mail('nys_sunset_policy', 'expiring_mail', $senator_email, NULL, $params, NULL, TRUE);
  }

}
