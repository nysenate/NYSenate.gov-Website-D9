<?php

namespace Drupal\nys_feeds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\nys_feeds\FeedState;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Service\NysFeedPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
   * Maintains the state of the process.
   */
  protected FeedState $state;

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
   * Resolves the feed plugin based on the requested series and/or parameters.
   */
  protected function resolvePlugin(string $series): ?NysFeedPluginBase {
    if (!$this->feedPluginManager->hasDefinition($series)) {
      $this->state->code = 404;
      $this->state->messages[] = 'Feed not found; returning list of available feeds';
      $series = 'feed_list';
    }
    try {
      /** @var \Drupal\nys_feeds\NysFeedPluginBase $handler */
      $handler = $this->feedPluginManager->createInstance($series);
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Exception trying to load feed @feed',
        ['@feed' => $series, '@msg' => $e->getMessage()]
      );
      $this->state->code = 500;
      $this->state->messages = ['Failed to load feed'];
      $handler = NULL;
    }

    return $handler;
  }

  /**
   * Returns the JSON response for a requested feed series.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $series
   *   The plugin id of the series being requested.
   */
  public function getFeed(Request $request, string $series = 'feed_list'): JsonResponse {
    // Start a new feed state.
    $this->state = new FeedState($request->query->all());

    // Try to find the requested feed, and let it work.
    /** @var \Drupal\nys_feeds\NysFeedPluginBase $plugin */
    if ($plugin = $this->resolvePlugin($series)) {
      $plugin->getFeed($this->state);
    }

    // Set up the response structure.
    $ret = [
      'status' => ($this->state->code === 200) ? 'OK' : 'error',
      'data' => $this->state->data ?: 'No data',
    ];
    if (count($this->state->messages)) {
      $ret['messages'] = $this->state->messages;
    }

    // Bye.
    return new JsonResponse($ret, $this->state->code);
  }

}
