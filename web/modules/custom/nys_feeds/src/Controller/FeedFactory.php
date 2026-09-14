<?php

namespace Drupal\nys_feeds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\nys_feeds\NysFeedPluginInterface;
use Drupal\nys_feeds\Service\NysFeedPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Routes a request feed to the proper handler.
 */
class FeedFactory extends ControllerBase {

  use LoggerChannelTrait;

  /**
   * NYS Feeds Plugin Manager Service.
   */
  protected NysFeedPluginManager $feedPluginManager;

  /**
   * Logging facility for NYS Feeds channel.
   */
  protected LoggerInterface $logger;

  /**
   * Repository for messages to add to the response.
   *
   * @var array
   */
  protected array $messages = [];

  /**
   * If populated, will override the response's HTTP status code.
   *
   * @var int
   */
  protected int $statusCode = 0;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_feeds\Service\NysFeedPluginManager $feedPluginManager
   *   NYS Feeds Plugin Manager service.
   */
  public function __construct(NysFeedPluginManager $feedPluginManager) {
    $this->feedPluginManager = $feedPluginManager;
    $this->logger = $this->getLogger('nys_feeds');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('nys_feeds.feed_manager'));
  }

  /**
   * If a plugin cannot be found, or if it is private, use "feed_list" instead.
   */
  protected function resolvePlugin(string $series): NysFeedPluginInterface {
    try {
      $definition = $this->feedPluginManager->getDefinition($series);
    }
    catch (\Throwable) {
      $definition = [];
    }
    if (($definition['private'] ?? '') !== FALSE) {
      $this->statusCode = 404;
      $this->messages[] = 'Feed not found; returning list of available feeds';
      $series = 'feed_list';
    }
    return $this->feedPluginManager->createInstance($series);
  }

  /**
   * Returns the JSON response for a requested feed series.
   *
   * @param string $series
   *   The plugin id of the series being requested.
   */
  public function getFeed(string $series = 'feed_list'): JsonResponse {
    // Instantiate the plugin.
    $plugin = $this->resolvePlugin($series);

    // Get the output; add any messages generated here.
    $output = $plugin->getFeed();
    $output->messages = array_filter($this->messages + $output->messages);
    if (!$output->status) {
      $output->status = ($output->statusCode == 200 || $this->statusCode == 200) ? 'ok' : 'error';
    }

    // Generate the response, but controller's status code takes precedence.
    $response = $plugin->getResponse($output);
    if ($this->statusCode) {
      $response->setStatusCode($this->statusCode);
    }

    // Send it.
    return $response;
  }

}
