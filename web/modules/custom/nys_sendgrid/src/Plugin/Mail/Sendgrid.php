<?php

namespace Drupal\nys_sendgrid\Plugin\Mail;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_sendgrid\Event\AfterFormatEvent;
use Drupal\nys_sendgrid\Event\AfterSendEvent;
use Drupal\nys_sendgrid\Events;
use Drupal\nys_sendgrid\Helper;
use Drupal\nys_sendgrid\TemplatesManager;
use Drupal\reroute_email\Constants\RerouteEmailConstants;
use Psr\Log\LoggerInterface;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MimeType;
use SendGrid\Mail\To;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a Drupal mail plugin leveraging the Sendgrid API.
 *
 * @Mail(
 *   id = "sendgrid_api",
 *   label = @Translation("Sendgrid API (NYS)"),
 *   description = @Translation("Sends a message through Sendgrid's API over
 *   HTTPS.")
 * )
 */
class Sendgrid implements MailInterface, ContainerFactoryPluginInterface {

  use MessengerTrait;
  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * An array of headers forbidden by SendGrid's API spec.
   *
   * @var array
   *
   * @see https://docs.sendgrid.com/api-reference/mail-send/errors#-Headers-Errors
   */
  protected static array $reservedHeaders = [
    'x-sg-id',
    'x-sg-eid',
    'received',
    'dkim-signature',
    'content-type',
    'content-transfer-encoding',
    'to',
    'from',
    'subject',
    'reply-to',
    'cc',
    'bcc',
  ];

  /**
   * A Sendgrid API client.
   *
   * @var \SendGrid
   */
  protected \SendGrid $client;

  /**
   * A Sendgrid Mail object to represent the message being sent.
   *
   * @var \SendGrid\Mail\Mail
   */
  protected Mail $mailObj;

  /**
   * A local reference of the message being sent.
   *
   * @var array
   */
  protected array $message;

  /**
   * The ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * The nys_sendgrid.settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $localConfig;

  /**
   * The system.site config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $siteConfig;

  /**
   * The reroute_email.settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $rerouteConfig;

  /**
   * Event Dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Logging facility for channel 'nys_sendgrid'.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $localLogger;

  /**
   * Constructor.
   *
   * @param \SendGrid $sendgrid
   *   A Sendgrid client object used to send the mail.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   A ModuleHandler service object.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   A ConfigFactory service object.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   An EventDispatcher service object.
   */
  public function __construct(\SendGrid $sendgrid, ModuleHandler $moduleHandler, ConfigFactory $config, EventDispatcherInterface $dispatcher) {
    $this->client = $sendgrid;
    $this->moduleHandler = $moduleHandler;
    $this->dispatcher = $dispatcher;
    $this->localConfig = $config->get('nys_sendgrid.settings');
    $this->siteConfig = $config->get('system.site');
    $this->rerouteConfig = $config->get('reroute_email.settings');
    $this->localLogger = $this->getLogger('nys_sendgrid');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $container->get('nys_sendgrid_client'),
          $container->get('module_handler'),
          $container->get('config.factory'),
          $container->get('event_dispatcher')
      );
  }

  /**
   * {@inheritDoc}
   *
   * Responsible for ensuring the existence of the Sendgrid\Mail\Mail object,
   * expected to be found in $message['params']['sendgrid_mail'].  If the
   * caller does not provide one, one is created.  The Mail object is then
   * processed as described below.  Each step will populate from data found
   * in the $message array, if necessary, and will cancel the email if a
   * problem is found. ($message['send'] = FALSE)
   *
   * The processing steps:
   *   - verify/set the sender
   *   - verify/set the personalizations (recipients)
   *   - implement rerouting found in reroute_email
   *   - verify/set the subject
   *   - verify/set the body.  If the body is created here, it will be HTML;
   *   - ensure Drupal email keys are added as Categories
   *   - ensure headers found in the message array are added
   *   - apply a template ID
   *   - dispatch the sendgrid_after_format event/hook
   *
   * NOTE: Canceling the email during format() will be a silent failure.  Any
   * listener that cancels should handle its own logging.
   *
   * @see \Drupal\Core\Mail\MailManager::doMail()
   */
  public function format(array $message): array {

    // Save the local reference.
    $this->message = $message;

    // Every processing step can throw, but should handle catch locally.
    // If any exceptions bubble up, cancel sending.
    try {

      // Ensure a Mail object is available.
      $this->mailObj = $this->getMailObject($this->message['params']['sendgrid_mail'] ?? NULL);

      // Process the Mail object.
      // All steps *should* handle their own exceptions.
      // @todo Create a format event, and let these steps be subscribers.
      $this->validateFrom()
        ->validateRecipients()
        ->setRerouting()
        ->validateSubject()
        ->validateBody()
        ->injectCategories()
        ->injectHeaders()
        ->applyTemplate();

    }
    catch (\Throwable $e) {
      $this->failSending('Sendgrid Mail object failed to validate.', ['message' => $e->getMessage()]);
    }

    // Make sure the modified object is saved to the message.
    $this->message['params']['sendgrid_mail'] = $this->mailObj;

    // Dispatch the "after format" event.
    // @phpstan-ignore-next-line
    $this->dispatcher->dispatch(new AfterFormatEvent($this->message), Events::AFTER_FORMAT);

    return $this->message;
  }

  /**
   * Provides a valid Sendgrid\Mail object.
   */
  protected function getMailObject(Mail $object = NULL): Mail {
    return $object instanceof Mail ? $object : new Mail();
  }

  /**
   * Sets a template for an outbound mail object.
   *
   * Ensures a template ID is assigned to a mail message, if template
   * assignment is not suppressed.  The order of precedence from high
   * to low is caller assignment, message ID, message module, default.
   *
   * @return $this
   */
  protected function applyTemplate(): self {
    // Get the list of templates.
    $templates = TemplatesManager::getTemplateAssignments();

    // Check if template assignment is suppressed.  Suppression could be in
    // the message params (preferred), or the global setting.
    $suppressed = ($this->message['params']['suppress_template'] ?? FALSE)
        || ($this->localConfig->get('suppress_template') ?? FALSE);

    // Templates pre-assigned by the caller take precedence.  If no template,
    // and not suppressed, discover and assign a template.
    if (!($this->mailObj->getTemplateId() || $suppressed)) {
      // Set up the check values.
      $caller = $this->message['params']['template_id'] ?? FALSE;
      $id = $this->message['id'] ?? FALSE;
      $mod = $this->message['module'] ?? FALSE;
      $default = $templates['_default_'] ?? FALSE;
      $actual = NULL;

      // If a template has been requested by the caller, use it.
      if ($caller) {
        $actual = $caller;
      }
      // Otherwise, check if the message ID has an assignment.
      elseif ($id && array_key_exists($id, $templates)) {
        $actual = $templates[$id];
      }
      // Otherwise, check if the module has an assignment.
      elseif ($mod && array_key_exists($mod, $templates)) {
        $actual = $templates[$mod];
      }
      // Otherwise, check if the default is set.
      elseif ($default) {
        $actual = $default;
      }

      // If a template has been found, set it.
      if ($actual) {
        try {
          $this->mailObj->setTemplateId($actual);
        }
        catch (\Throwable $e) {
          $this->failSending(
                'Email failed while assigning template (id=%id, actual=%actual)', [
                  'message' => $e->getMessage(),
                  '%actual' => $actual,
                ]
            );
        }
      }
    }
    return $this;
  }

  /**
   * Macro function to record an email failing to send.
   *
   * @param string $message
   *   The log message to record.
   * @param array $vars
   *   The variables to attach to the log message.
   */
  protected function failSending(string $message = '', array $vars = []) {
    $vars += ['%id' => $this->message['id'] ?? '-no id-'];
    $this->localLogger->error($message, $vars);
    $this->messenger()
      ->addError($this->t('Unable to send e-mail. Contact the site administrator if the problem persists.'));
    $this->message['send'] = FALSE;
  }

  /**
   * Adds custom message headers to the mail object.
   *
   * Adds any headers found in the message to the mail object.  Note that
   * these are filtered through SendGrid's list of reserved headers, such
   * as "Content-Type".  This method considers only headers added to the
   * message array.  The original caller is responsible for any headers it
   * adds to a pre-formed mail object.
   *
   * @see https://docs.sendgrid.com/api-reference/mail-send/errors#-Headers-Errors
   *
   * @return $this
   */
  protected function injectHeaders(): self {
    foreach (($this->message['headers'] ?? []) as $key => $val) {
      // NOTE: There can be differences in capitalization.
      if (!in_array(strtolower($key), static::$reservedHeaders)) {
        $this->mailObj->addGlobalHeader($key, $val);
      }
    }
    return $this;
  }

  /**
   * Injects the normal Drupal mail keys as Categories.
   *
   * If category assignments have not been suppressed (through the config
   * UI, or message['params']), ensure the normal Drupal keys are added
   * as categories on the message.
   *
   * A message can have no more than 10 categories, and the API requires
   * all category entries be unique.  The library has no facility to
   * remove categories once added, so some voodoo is necessary. This method
   * does not ensure uniqueness of existing categories - it only ensures
   * that the categories based on Drupal's keys are not duplicates.
   *
   * @return $this
   */
  protected function injectCategories(): self {
    // @todo Cannot remove categories in current lib.
    // Work is only needed if 'module' is populated (should be!).
    if ($this->message['module'] ?? '') {
      // Check for suppression, which can be in config or message params.
      $suppressed = ($this->message['params']['suppress_categories'] ?? FALSE)
            || $this->localConfig->get('suppress_categories');
      if (!$suppressed) {
        // Get the list of current categories.
        $categories = array_map(
              function ($v) {
                  return $v->getCategory();
              },
              $this->mailObj->getCategories() ?? []
          );

        // Build which keys will be added.
        $add = array_filter(
              [
                $this->message['module'] ?? '',
                $this->message['key'] ?? '',
                $this->message['id'] ?? '',
              ]
          );

        // Categories must be unique; ensure no duplicates are added.
        try {
          $this->mailObj->addCategories(array_diff($add, $categories));
        }
        catch (\Throwable $e) {
          $this->failSending(
          'Email failed while validating categories (id=%id)', [
            'message' => $e->getMessage(),
            'add' => $add,
            'categories' => $categories,
          ]
            );
        }
      }
    }

    return $this;
  }

  /**
   * Validates the body content of an outbound email.
   *
   * @return $this
   */
  protected function validateBody(): self {
    // If no contents are set, try to generate a body from $message.
    if (!$this->mailObj->getContents()) {
      // If the preferred body is not populated, fallback to the alternate
      // style from D7 legacy.
      $body = ($this->message['body'] ?? [])
            ?: ($this->message['params']['body'] ?? []);

      // Typecast each body part to string so addContent() doesn't cry.
      if (!is_array($body)) {
        $body = [$body];
      }
      $body = implode(
            "\n\n",
            array_map(
                function ($i) {
                    return (string) $i;
                }, $body
            )
        );

      // Use content type from params, or assume HTML email.
      $content_type = $this->message['params']['Content-Type'] ?? MimeType::HTML;

      // Ensure $body uses proper line breaks based on content type.
      if ($content_type == MimeType::HTML) {
        // For conversion to HTML line breaks, removing comments is helpful.
        $body = preg_replace('/<!-- .* -->/', '', $body);
        $body = preg_replace('/\n+/', '<br /><br />', $body);
      }

      // Add body (or fallback) as content. API will break if body is empty.
      try {
        $this->mailObj->addContent($content_type, $body ?: 'blank content');
      }
      catch (\Throwable $e) {
        $this->failSending(
          'Email failed while validating body (id=%id, ct=%ct)', [
            '%message' => $e->getMessage(),
            '%body' => $body,
            '%ct' => $content_type,
          ]
          );
      }
    }

    return $this;
  }

  /**
   * Validates the subject.
   *
   * @return $this
   */
  protected function validateSubject(): self {
    // Make sure the global Subject is set.
    if (!$this->mailObj->getGlobalSubject()) {
      $subject = $this->message['params']['subject'] ?? ($this->message['subject'] ?? '');
      try {
        $this->mailObj->setGlobalSubject($subject);
      }
      catch (\Throwable $e) {
        $this->failSending(
              'Email failed while validating subject (id=%id, subj=%subj)', [
                'message' => $e->getMessage(),
                '%subj' => $subject,
              ]
          );
      }
    }

    return $this;
  }

  /**
   * Reroutes all recipients in a Sendgrid\Mail object.
   *
   * NOTE: The reroute_email module has some issues with consistent
   * capitalization of header keys.  If rerouting mysteriously breaks,
   * that would be a good starting point.
   *
   * @return $this
   */
  protected function setRerouting(): self {
    // Handle rerouting, if the module is enabled.
    if (Helper::detectMailRerouting()) {
      // Get the new destination.  If reroute headers have been set, this
      // *should* be in $message['to'].  Otherwise, get from the system.
      if ($this->message['headers']['X-Rerouted-Mail-Key'] ?? NULL) {
        $dest = $this->message['to'] ?? '';
      }
      else {
        $dest = $this->rerouteConfig->get(RerouteEmailConstants::REROUTE_EMAIL_ADDRESS) ?: $this->siteConfig->get('site_mail');
      }

      // If a reroute address is found, update each personalization.
      if ($dest) {
        try {
          foreach (($this->mailObj->getPersonalizations() ?? []) as $one_person) {
            // Get all the possible recipients.
            $recipients = Helper::getAllRecipients($one_person);

            // Iterate through recipients and change the name/email, if needed.
            foreach ($recipients as $recipient) {
              $email = $recipient->getEmailAddress();
              if ($email != $dest) {
                $name = $recipient->getName();
                $recipient->setName("$email|$name");
                $recipient->setEmailAddress($dest);
              }
              elseif (!$recipient->getName()) {
                $original_email = $this->message['headers']['X-Rerouted-Original-to'] ?? '';
                $new_name = 'reroute_email ' .
                                ($original_email ? 'from ' . $original_email : '<unknown>');
                $recipient->setName($new_name);
              }
            }
          }
        }
        catch (\Throwable $e) {
          $this->failSending(
                'Email failed during rerouting attempt. (id=%id, dest=%dest)', [
                  'message' => $e->getMessage(),
                  '%dest' => $dest,
                ]
            );
        }
      }
    }

    return $this;
  }

  /**
   * Validates the recipients for an outbound mail object.
   *
   * Ensures at least one personalization with at least one recipient is set.
   * If not, it attempts to set one from the message meta-data (i.e.,
   * $message['to'] and $message['params']['to']).
   *
   * @return $this
   */
  protected function validateRecipients(): self {

    // Detect any existing recipients.
    $persons = $this->mailObj->getPersonalizations() ?? [];
    $found_to = FALSE;
    foreach ($persons as $one_person) {
      if (count(Helper::getAllRecipients($one_person))) {
        $found_to = TRUE;
      }
    }

    // If no recipients were found, try to create one from message params
    // or metadata.  Note that this info *may* already have been rerouted.
    if (!$found_to) {
      $to_addr = Helper::parseAddress($this->message['params']['to'] ?? $this->message['to']);
      try {
        $this->mailObj->addTo(new To($to_addr[0], $to_addr[1]));
      }
      catch (\Throwable $e) {
        $this->failSending(
              'Email failed due to poorly-formed "To" address (id=%id, addr=%addr)', [
                'message' => $e->getMessage(),
                '%addr' => $to_addr,
              ]
          );
      }
    }

    return $this;
  }

  /**
   * Validates the "From" address for an outbound mail object.
   *
   * @return $this
   */
  protected function validateFrom(): self {

    // If a "From" address is not found, make one.
    if (!(($this->mailObj->getFrom() instanceof From)
          && $this->mailObj->getFrom()->getEmail())
      ) {
      // Parse the email address from the message.
      $full_from = $this->message['params']['from'] ?? $this->message['from'];
      $from_email = Helper::parseAddress($full_from);

      // Create a "From".  Report and fail on error.
      try {
        $this->mailObj->setFrom(new From($from_email[0], $from_email[1]));
      }
      catch (\Throwable $e) {
        $this->failSending(
              'Email failed due to poorly-formed "From" address (id=%id, addr=%addr)', [
                'message' => $e->getMessage(),
                '%addr' => $from_email,
              ]
          );
      }
    }

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function mail(array $message): bool {
    // Set up some references.
    try {
      $params = &$message['params'];
      $sgm = &$params['sendgrid_mail'];
    }
    catch (\Throwable $e) {
      $this->localLogger
        ->critical('mail() did not find a well-formed Mail object.', ['%message' => $e->getMessage()]);
      return FALSE;
    }

    // Try to send the message.
    try {
      $response = $this->client->send($sgm);
      $response_code = $response->statusCode() ?? 0;
    }
    catch (\Throwable $e) {
      $response = NULL;
      $response_code = 0;
      $this->localLogger
        ->error('Sending mail generated an exception.', ['%message' => $e->getMessage()]);
    }

    // Send the response back with the object.
    $sgm->response = $response;

    // Check for success.  We consider a 200/202 response code successful.
    $success = in_array($response_code, ['200', '202']);
    // If successful, dispatch the after.send event.
    if ($success) {
      // @phpstan-ignore-next-line
      $this->dispatcher->dispatch(new AfterSendEvent($message), Events::AFTER_SEND);
    }
    // If not successful, but not an exception, log the reason.
    elseif ($response) {
      $this->localLogger
        ->error('Sendgrid API rejected a mail attempt.', ['%response' => $response->body()]);
    }

    // Return the success/failure.
    return $success;
  }

}
