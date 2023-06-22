<?php

namespace Drupal\nys_sunset_policy\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sunset Policy Expiring Queue Worker.
 *
 * @QueueWorker(
 *   id = "nys_sunset_expiring_queue",
 *   title = @Translation("Sunset Policy Expiring Queue"),
 *   cron = {"time" = 60}
 * )
 */
final class SunsetExpiringQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
    /**
     * @var \Drupal\node\NodeInterface $node
     */
    $node = $this->nodeStorage->load($data['data']);
    if ($node instanceof NodeInterface) {
      $host = \Drupal::request()->getHost();
      $body['message']['expiring'] = date('l M jS Y', strtotime($node->field_expiration_date->getValue()[0]['value']));
      $body['message']['alias'] = $host . $node->toUrl()->toString();
      $body['message']['url'] = $host . '/node/' . $data['data'];
      $body['message']['title'] = $node->getTitle();
      $params['message'] = $this->renderMailBodyExpiring($body);
      $subject = 'Content will expire soon - ' . $node->getTitle();
      $params['title'] = $subject;
      $key = 'expiring_mail';
      $module = 'nys_sunset_policy';
      $params = ['subject' => $subject, 'body' => $params['message']];
      $senator_terms = $node->get('field_senator_multiref')->referencedEntities();
      $senator_emails = [];
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      foreach ($senator_terms as $senator_term) {
        if ($senator_term->get('field_active_senator')->getValue()[0]['value']) {
          $senator_emails[] = $senator_term->get('field_email')->getValue()[0]['value'];
        }
        else {
          $node->set('field_last_notified', date('Y-m-d\TH:i:s', time()));
          $node->save();
        }
      }
    }
    try {
      if (!empty($senator_emails)) {
        foreach ($senator_emails as $senator_email) {
          $mailManager = \Drupal::service('plugin.manager.mail');
          $mailManager->mail($module, $key, $senator_email, $langcode, $params, NULL, TRUE);
          $node->set('field_last_notified', date('Y-m-d\TH:i:s', time()));
          $node->save();
        }
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('nys_sunset_policy')
        ->error('Unable to send expiring mail for node/' . $data['data'], ['%message' => $e->getMessage()]);
    }
  }

  /**
   * Run the body through the expiring mail template.
   *
   * @param mixed $body
   *   Array of body data.
   */
  public function renderMailBodyExpiring($body) {
    $message = '';
    $body_data = [
      '#theme' => 'expiring_mail',
      '#message' => $body['message'],
    ];
    $message = \Drupal::service('renderer')->render($body_data);
    return (string) $message;
  }

}
