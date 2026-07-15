<?php

namespace Drupal\nys_openleg_imports\Controller;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Internal endpoint for dispatching Cloudflare BAN requests from CLI context.
 *
 * Drupal's pantheon_advanced_page_cache module dispatches CDN BANs by calling
 * pantheon_clear_edge_keys() inside CacheTagsInvalidator::invalidateTags().
 * That function is only available in the Pantheon web-worker PHP environment;
 * CLI processes (e.g. the OpenLeg bill importer running via Drush) do not have
 * access to it, so cache tag invalidations from CLI saves silently skip the CDN
 * purge step.
 *
 * This controller bridges that gap: the importer makes a POST request to this
 * endpoint (a real HTTP request), which runs in web-worker context where
 * pantheon_clear_edge_keys() IS available. Calling invalidateTags() here
 * dispatches the BAN for the provided tags, keeping the CDN in sync with
 * content imported via CLI.
 *
 * Authentication is via a pre-shared key stored in settings.php under
 * $settings['nys_purge_key']. The key is validated using hash_equals() to
 * prevent timing-based enumeration attacks.
 *
 * @see \Drupal\nys_openleg_imports\Commands\OpenlegImport::dispatchCachePurge()
 */
class PurgeCacheController extends ControllerBase {

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * Constructor.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Invalidates the provided cache tags and dispatches the CDN BAN.
   *
   * Expects:
   *   - Method: POST
   *   - Header: Authorization: Bearer {nys_purge_key}
   *   - Body (JSON): {"tags": ["node:123", "node:456", ...]}
   *
   * Returns 200 on success, 400 on malformed input, 403 on auth failure.
   * Error details are intentionally minimal to avoid leaking internal state.
   */
  public function purge(Request $request): JsonResponse {
    // Validate the pre-shared key using a timing-safe comparison.
    $expected_key = Settings::get('nys_purge_key', '');
    if (empty($expected_key)) {
      // Key not configured — refuse all requests so misconfigured environments
      // do not accidentally accept purge calls with an empty token.
      return new JsonResponse(['error' => 'Forbidden'], 403);
    }

    $auth_header = $request->headers->get('Authorization', '');
    $provided_key = str_starts_with($auth_header, 'Bearer ')
      ? substr($auth_header, 7)
      : '';

    if (!hash_equals($expected_key, $provided_key)) {
      return new JsonResponse(['error' => 'Forbidden'], 403);
    }

    // Decode and validate the request body.
    $body = json_decode($request->getContent(), TRUE);
    if (!is_array($body) || empty($body['tags']) || !is_array($body['tags'])) {
      return new JsonResponse(['error' => 'Bad Request: tags array required'], 400);
    }

    // Ensure every tag is a non-empty string before passing to
    // invalidateTags().
    $tags = array_values(array_filter($body['tags'], 'is_string'));
    if (empty($tags)) {
      return new JsonResponse(['error' => 'Bad Request: no valid tags provided'], 400);
    }

    $this->cacheTagsInvalidator->invalidateTags($tags);

    return new JsonResponse(['purged' => count($tags)], 200);
  }

}
