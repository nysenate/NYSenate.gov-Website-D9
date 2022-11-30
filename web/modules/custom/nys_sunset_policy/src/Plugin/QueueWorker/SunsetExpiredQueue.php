<?php

namespace Drupal\nys_sunset_policy\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Custom Queue Worker.
*
* @QueueWorker(
*   id = "nys_sunset_expired_queue",
*   title = @Translation("Sunset Policy Expired Queue"),
*   cron = {"time" = 60}
* )
*/
final class SunsetExpiredQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Creates a new NodePublishBase object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node')
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
    $node = $this->nodeStorage->load($data->nid);
    $host = \Drupal::request()->getHost();
    $params['message']['expired'] = date('l M jS Y', strtotime($node->field_expiration_date->getValue()[0]['value']));
    $params['message']['alias'] = $host . $node->toUrl()->toString();
    $params['message']['url'] = $host . '/node/' . $data->nid;
    $params['message']['title'] = $node->getTitle();
    $subject = 'Content will expire soon - ' . $node->getTitle();
    $params['title'] = $subject;
    $key = 'expired_mail';
    $module = 'nys_sunset_policy';
    $params = ['subject' => $subject, 'body' => $params['message']];
    $senator_terms = $node->get('field_senator_multiref')->referencedEntities();
    $senator_email = '';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    foreach ($senator_terms as $senator_term) {
      $senator_email = $senator_term->get('field_email')->getValue()[0]['value'];
    }
    $mailManager = \Drupal::service('plugin.manager.mail');
    try {
      $mailManager->mail($module, $key, $senator_email, $langcode, $params, NULL, TRUE);
      $node->set('field_last_notified', date('Y-m-d\TH:i:s', time()));
      $node->setPublished(FALSE);
      $node->save();
    }
    catch (\Throwable $e) {
      \Drupal::logger('nys_sunset_policy')
        ->error('Unable to send expired mail for node/' . $data->nid, ['message' => $e->getMessage()]);
    }
  }

}
