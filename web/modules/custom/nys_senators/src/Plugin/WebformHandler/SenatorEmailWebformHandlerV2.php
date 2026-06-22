<?php

namespace Drupal\nys_senators\Plugin\WebformHandler;

use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform senator email handler for composite-based contact forms.
 *
 * Reads address data from an nys_contact_composite element rather than
 * the flat address/city/zip fields used by nys_senator_email.
 *
 * @WebformHandler(
 *   id = "nys_senator_email_v2",
 *   label = @Translation("Senator Email V2"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a submission to the senator email address. Reads address from the NYS Contact Composite element."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SenatorEmailWebformHandlerV2 extends EmailWebformHandler {

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
    $data = $webform_submission->getData();

    // Gets term ID from submission and loads the senator term.
    $tid = $data['tid'] ?? '';
    $senator_term = !empty($tid)
      ? $this->entityTypeManager->getStorage('taxonomy_term')->load($tid)
      : NULL;

    // Override email recipient with senator's address.
    if ($senator_term instanceof TermInterface
          && $senator_term->bundle() === 'senator'
      ) {
      if ($senator_term->hasField('field_email')
            && !$senator_term->get('field_email')->isEmpty()
        ) {
        $message['to_mail'] = $senator_term->field_email->value;
      }

      // Use press inquiries address for press inquiry submissions.
      $inquiry_type = $data['inquiry_type'] ?? '';
      if ($inquiry_type === 'press_inquiry') {
        if ($senator_term->hasField('field_press_inquiries')
              && !$senator_term->get('field_press_inquiries')->isEmpty()
          ) {
          $message['to_mail'] = $senator_term->field_press_inquiries->value;
        }
      }
    }

    // Read address sub-keys from the first nys_contact_composite found in
    // submission data, regardless of the element's machine name on the form.
    $composite = [];
    foreach ($data as $value) {
      if (is_array($value) && array_key_exists('address_line1', $value)) {
        $composite = $value;
        break;
      }
    }
    $address = $composite['address_line1'] ?? '';
    $city = $composite['locality'] ?? '';
    $zip = $composite['postal_code'] ?? '';

    // Build SD postal address URL.
    $find_senator_url = Url::fromUri('base:find-my-senator');
    $find_senator_url->setAbsolute(TRUE);
    $sd_url = $find_senator_url->toString()
      . '?search=true&addr1=' . urlencode($address)
      . '&city=' . urlencode($city)
      . '&zip5=' . urlencode($zip);
    $message['body'] = preg_replace(
      '/\[sd-placeholder\]/',
      $sd_url . '$1',
      $message['body']
    );

    return parent::sendMessage($webform_submission, $message);
  }

}
