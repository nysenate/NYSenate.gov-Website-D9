<?php

namespace Drupal\nys_senators\Plugin\WebformHandler;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
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
      ) {

      // Set the general inquiry email as the default value.
      if (!$senator_term->get('field_email')->isEmpty()
            && $senator_term->hasField('field_email')
        ) {
        $message['to_mail'] = $senator_term->field_email->value;
      }

      $inquiry_type = $webform_submission->getData()['inquiry_type'];
      // Only override the value if the inquiry type is press_inquiry.
      if ($inquiry_type == 'press_inquiry') {
        if (!$senator_term->get('field_press_inquiries')->isEmpty()
              && $senator_term->hasField('field_press_inquiries')
          ) {
          $message['to_mail'] = $senator_term->field_press_inquiries->value;
        }
      }
    }

    // Gets address, city & zip values from webform submission.
    $address = $webform_submission->getData()['address'] ?? '';
    $city = $webform_submission->getData()['city'] ?? '';
    $zip = $webform_submission->getData()['zip'] ?? '';

    // Build SD postal address URL.
    $find_senator_url = Url::fromUri('base:find-my-senator');
    $find_senator_url->setAbsolute(TRUE);
    $sd_url = $find_senator_url->toString() . '?search=true&addr1=' . urlencode($address) . '&city=' . urlencode($city) . '&zip5=' . urlencode($zip);
    $message['body'] = preg_replace('/\[sd-placeholder\]/', $sd_url . '$1', $message['body']);

    return parent::sendMessage($webform_submission, $message);
  }

}
