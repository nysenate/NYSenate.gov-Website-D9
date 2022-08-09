<?php

namespace Drupal\nys_senators\Plugin\WebformHandler;

use Drupal\taxonomy\TermInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform senator email handler.
 *
 * @WebformHandler(
 *   id = "nys_senator_email",
 *   label = @Translation("Senator Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission to a different senator email address per microsite page."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SenatorEmailWebformHandler extends EmailWebformHandler {

  use StringTranslationTrait;

  /**
   * The EntityType Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    // Gets term ID from submission.
    $tid = $webform_submission->getData()['tid'] ?? '';

    // Loads the senator term.
    $senator_term = !empty($tid) ? $this->entityTypeManager->getStorage('taxonomy_term')->load($tid) : NULL;

    // Checks for senator email value and overrides email recipient.
    if ($senator_term instanceof TermInterface
        && $senator_term->bundle() === 'senator'
        && $senator_term->hasField('field_email')
        && !$senator_term->get('field_email')->isEmpty()) {
      $message['to_mail'] = $senator_term->field_email->value;
    }

    return parent::sendMessage($webform_submission, $message);
  }

}
