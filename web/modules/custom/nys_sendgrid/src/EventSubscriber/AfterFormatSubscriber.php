<?php

namespace Drupal\nys_sendgrid\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_sendgrid\Event\AfterFormatEvent;
use Drupal\nys_sendgrid\Events;
use Drupal\nys_sendgrid\Helper;
use Drupal\nys_sendgrid\TemplatesManager;
use Psr\Log\LoggerInterface;
use SendGrid\Mail\ClickTracking;
use SendGrid\Mail\Ganalytics;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MimeType;
use SendGrid\Mail\OpenTracking;
use SendGrid\Mail\Subject;
use SendGrid\Mail\TemplateId;
use SendGrid\Mail\TrackingSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for nys_sendgrid after.format event.
 */
class AfterFormatSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config->get('nys_sendgrid.settings');
    $this->logger = $this->getLogger('nys_sendgrid');
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      Events::AFTER_FORMAT => 'afterFormat',
    ];
  }

  /**
   * Listens for nys_sendgrid.after.format event.
   */
  public function afterFormat(AfterFormatEvent $event) {

    // Only act if there is a valid Sendgrid\Mail object.
    $sgm = $event->message['params']['sendgrid_mail'] ?? NULL;
    if (($event->message['send'] ?? FALSE) && $sgm instanceof Mail) {

      // Get a reference to the template.
      $template_id = $sgm->getTemplateId();
      $template_id = ($template_id instanceof TemplateId)
            ? $template_id->getTemplateId()
            : '';
      $template = TemplatesManager::getTemplate($template_id);

      // Add the site substitutions, if configured.
      if ($_ENV['site_url'] ?? FALSE) {
        $this->addSubstitutionSiteUrl($event, $_ENV['site_url']);
      }

      // Add some substitutions common to all templates.
      if ($template && $template->isDynamic() && $this->config->get('content_substitution')) {
        $this->addSubstitutionBodySubject($event);
      }

      // Set up tracking, if not suppressed.
      if (!($event->message['params']['suppress_tracking'] ?? FALSE)) {
        $this->addTracking($event);
      }

      // Set sandbox mode if not on prod, and no rerouting is detected.
      if (Helper::detectSendgridSandbox()) {
        $this->enableSandbox($event);
      }
    }
  }

  /**
   * Convenience wrapper to get the Mail object from the message.
   */
  protected function getMail(AfterFormatEvent $event) : Mail {
    return $event->message['params']['sendgrid_mail'];
  }

  /**
   * Enables Sendgrid's sandbox mode for a message.
   */
  protected function enableSandbox(AfterFormatEvent $event) {
    $sgm = $this->getMail($event);
    try {
      $sgm->enableSandBoxMode();
      $this->logger->info('SendGrid sandbox engaged');
      $this->messenger()
        ->addWarning($this->t('SendGrid sandbox is engaged.  Configure mail rerouting to disengage sandbox.'));
    }
    catch (\Throwable $e) {
      // If sandbox was desired and could not be set, cancel the email.
      $event->message['send'] = FALSE;
      $m = $this->t('SendGrid sandbox could not be engaged; email cancelled.');
      $this->logger->error($m, ['%message' => $e->getMessage()]);
      $this->messenger()->addError($m);
    }
  }

  /**
   * Adds the site URL token to a Personalization.
   */
  protected function addSubstitutionSiteUrl(AfterFormatEvent $event, string $url) {
    // @todo Revisit the need for this after refactoring Sendgrid's templates.
    try {
      foreach (($this->getMail($event)->getPersonalizations() ?? []) as $person) {
        $person->addSubstitution('%instance_url%', $url);
        $person->addSubstitution('instanceUrl', $url);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error("Could not add site URL substitutions", ['%message' => $e->getMessage()]);
    }
  }

  /**
   * Adds substitution tokens for the body and subject.
   */
  protected function addSubstitutionBodySubject(AfterFormatEvent $event) {
    $sgm = $this->getMail($event);

    // Find the "proper" content.  The first HTML content is preferred,
    // followed by first TEXT content.
    $subj_token = $this->config->get('content_token_subject') ?: 'subject';
    $body_token = $this->config->get('content_token_body') ?: 'body';
    $body_content = $text_content = '';
    foreach ($sgm->getContents() as $one_content) {
      if ($one_content->getType() == MimeType::HTML && !$body_content) {
        $body_content = (string) $one_content->getValue();
      }
      if ($one_content->getType() == MimeType::TEXT && !$text_content) {
        $text_content = (string) $one_content->getValue();
      }
    }
    if (!$body_content && $text_content) {
      $body_content = $text_content;
    }

    // Add the body and subject substitution tokens.
    try {
      $g_subject = $sgm->getGlobalSubject();
      foreach (($sgm->getPersonalizations() ?? []) as $person) {
        // Get the personalized subject, or the global subject.
        $subject = $person->getSubject() ?: $g_subject;
        $subj_content = ($subject instanceof Subject)
                ? $subject->getSubject()
                : '';

        // Add the body/subject substitutions.
        $person->addSubstitution($body_token, $body_content);
        $person->addSubstitution($subj_token, $subj_content);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error("Could not add body/subject substitutions", ['%message' => $e->getMessage()]);
    }
  }

  /**
   * Adds tracking options to a Mail object.
   */
  protected function addTracking(AfterFormatEvent $event) {
    try {
      // Google Analytics Tracking.
      $g_analytics = new Ganalytics();
      $g_analytics->setEnable(TRUE);
      $g_analytics->setCampaignMedium('email');
      $g_analytics->setCampaignSource('ny_state_senate');
      if ($t_name = ($event->message['params']['GA_CampaignName'] ?? '')) {
        $g_analytics->setCampaignName($t_name);
      }
      if ($t_event = ($event->message['params']['GA_CampaignContent'] ?? '')) {
        $g_analytics->setCampaignContent($t_event);
      }

      // Enable Email Open Tracking.
      $open_tracking = new OpenTracking();
      $open_tracking->setEnable(TRUE);

      // Enable Email Click Tracking.
      $click_tracking = new ClickTracking();
      $click_tracking->setEnable(TRUE);
      $click_tracking->setEnableText(TRUE);

      // Prepare Track Settings Object.
      $alert_tracking = new TrackingSettings(
            $click_tracking,
            $open_tracking,
            NULL,
            $g_analytics
        );

      // Add the tracking.
      $this->getMail($event)->setTrackingSettings($alert_tracking);
    }
    catch (\Throwable $e) {
      $this->logger->error("Could not add tracking options", ['%message' => $e->getMessage()]);
    }
  }

}
