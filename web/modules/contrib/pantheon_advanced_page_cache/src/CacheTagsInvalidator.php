<?php

namespace Drupal\pantheon_advanced_page_cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cache tags invalidator implementation that invalidates the Pantheon edge.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $do_not_run_urls = [
      // There is a weird interaction with metatag that clear local_tasks key
      // and therefore lots of cached pages.
      '/core/install.php',
    ];
    $current_request = $this->requestStack->getCurrentRequest();
    if ($current_request && in_array($current_request->getBaseUrl(), $do_not_run_urls)) {
      return;
    }
    if (function_exists('pantheon_clear_edge_keys')) {
      pantheon_clear_edge_keys($tags);
    }
  }

}
