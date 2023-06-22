<?php

namespace Drupal\nys_slack\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelTrait;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Service class for Slack messaging.
 */
class Slack {

  use LoggerChannelTrait;

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $config;

  /**
   * Config settings for NYS Slack.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $localConfig;

  /**
   * Message title.
   *
   * @var string
   */
  protected string $title;

  /**
   * The body of the message.
   *
   * @var string
   */
  protected string $message;

  /**
   * Array of Attachment objects.
   *
   * @var array
   */
  protected array $attachments = [];

  /**
   * A Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $client;

  /**
   * A logging facility.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config, Client $client) {
    $this->config = $config;
    $this->localConfig = $config->get('nys_slack.settings');
    $this->client = $client;
    $this->logger = $this->getLogger('nys_slack');
  }

  /**
   * Sends a message to Slack.
   */
  public function send($message = '') {
    $url = $this->localConfig->get('webhook_url');
    $sent = FALSE;
    $response = NULL;

    // Proceed only if the slack URL is set.
    if (empty($url)) {
      $this->logger->warning('Slack URL is not set!');
    }
    else {
      $title = $this->getTitle();
      $message = $message ?: $this->getMessage();
      $payload = [
        'text' => "$title\n$message",
        'blocks' => [
        [
          'type' => 'header',
          'text' => ['type' => 'plain_text', 'text' => $title],
        ],
        [
          'type' => 'section',
          'text' => ['type' => 'plain_text', 'text' => $message],
        ],
        ],
      ];
      if (count($this->attachments)) {
        $context = ['type' => 'context', 'elements' => []];
        foreach ($this->attachments as $val) {
          $context['elements'][] = [
            'type' => 'plain_text',
            'text' => $val,
          ];
        }
        $payload['blocks'][] = $context;
      }

      try {
        $response = $this->client->request('POST', $url, ['json' => $payload]);
        $sent = TRUE;
      }
      catch (\Throwable $e) {
        $this->logger->error('Exception generated while sending message to slack %message', ['%message' => $e->getMessage()]);
      }
    }

    if (!$sent) {
      if ($response instanceof ResponseInterface) {
        $code = $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
      }
      else {
        $code = 'n/a';
        $phrase = 'no response found';
      }
      $this->logger->error(
            'Failed to send Slack message, status=%status, phrase=%phrase, message=%message', [
              '%status' => $code,
              '%phrase' => $phrase,
              '%message' => $message,
            ]
        );
    }

    $this->init();

  }

  /**
   * Builds the message title.
   */
  public function getTitle(): string {
    return $this->title ?? $this->getDefaultTitle();
  }

  /**
   * Sets the message title.
   */
  public function setTitle($string = ''): Slack {
    $this->title = $string ?: $this->getDefaultTitle();
    return $this;
  }

  /**
   * Builds a default title.
   */
  public function getDefaultTitle(): string {
    $env = $_ENV['PANTHEON_ENVIRONMENT'] ?? 'Unknown';
    return "Report from Web Site (ENV: $env)";
  }

  /**
   * Sets the message object back to default settings.
   */
  public function init(): Slack {
    return $this->setTitle()->setMessage()->clearAttachments();
  }

  /**
   * Clears all attachments.
   */
  public function clearAttachments(): Slack {
    $this->attachments = [];
    return $this;
  }

  /**
   * Gets the message body.
   */
  public function getMessage(): string {
    return $this->message ?? '';
  }

  /**
   * Sets the message body.
   */
  public function setMessage(string $message = ''): Slack {
    $this->message = $message;
    return $this;
  }

  /**
   * Adds text as a message attachment.
   */
  public function addAttachment(string $text): Slack {
    $this->attachments[] = $text;
    return $this;
  }

  /**
   * Gets current attachments.
   */
  public function getAttachments(): array {
    return $this->attachments;
  }

}
