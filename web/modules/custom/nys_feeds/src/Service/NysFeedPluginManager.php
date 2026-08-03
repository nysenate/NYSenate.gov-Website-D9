<?php

namespace Drupal\nys_feeds\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_feeds\FeedInput;
use Drupal\nys_feeds\NysFeedPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin Manager for NysFeed plugins.
 */
class NysFeedPluginManager extends DefaultPluginManager {

  /**
   * Drupal's Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, RequestStack $request_stack) {
    parent::__construct(
      'Plugin/NysFeed',
      $namespaces,
      $module_handler,
      'Drupal\nys_feeds\NysFeedPluginInterface',
      'Drupal\nys_feeds\Attribute\NysFeed',
    );
    $this->setCacheBackend($cache_backend, 'nys_feeds');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritDoc}
   *
   * Override to ensure a FeedInput object is passed in configuration, and that
   * exceptions fallback to a FeedPluginError instance.
   */
  public function createInstance($plugin_id, array $configuration = []): NysFeedPluginInterface {
    if (!array_key_exists('input', $configuration)) {
      $configuration['input'] = $this->getFeedInput();
    }

    /** @var \Drupal\nys_feeds\NysFeedPluginInterface $ret */
    try {
      $ret = parent::createInstance($plugin_id, $configuration);
    }
    catch (\Throwable $e) {
      // If this one throws, well.. we've done all we can.
      $ret = parent::createInstance('feed_error', $configuration + ['exception' => $e]);
    }

    return $ret;
  }

  /**
   * Creates a new FeedInput with the best request we can find.
   */
  public function getFeedInput(): FeedInput {
    return new FeedInput($this->requestStack->getCurrentRequest() ?? Request::createFromGlobals());
  }

}
