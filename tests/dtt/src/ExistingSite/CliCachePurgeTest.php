<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\Core\Site\Settings;

/**
 * Verifies the CLI-save → CDN-purge-endpoint flow for bill nodes.
 *
 * Background
 * ----------
 * pantheon_advanced_page_cache dispatches Cloudflare BANs via
 * pantheon_clear_edge_keys(), a PHP function that is only available in
 * Pantheon's web-worker environment. CLI processes (e.g. the OpenLeg bill
 * importer running via Drush) do not have access to this function, so direct
 * entity API saves from CLI correctly invalidate the Drupal/Redis page cache
 * but silently skip the Cloudflare purge step.
 *
 * PurgeCacheController bridges this gap: after the importer saves nodes it
 * POSTs the affected node:{nid} tags to /api/internal/cache/purge, which runs
 * in web-worker context and therefore can dispatch the BAN.
 *
 * This test class verifies the two sides of that contract:
 *
 *  1. testCliSaveDoesNotInvalidateCdnCache() — confirms that a direct
 *     $node->save() (the CLI code path) does NOT bust Cloudflare. If this
 *     starts failing (i.e. CF shows MISS after a CLI save), it means
 *     pantheon_clear_edge_keys() has become available in the CLI environment
 *     and the purge endpoint is no longer needed for this use-case.
 *
 *  2. testPurgeEndpointInvalidatesCdnCacheAfterCliSave() — confirms the full
 *     end-to-end flow: CLI save → CF still HIT → POST purge endpoint → CF MISS
 *     → re-warm to HIT.
 *
 * Both tests require $settings['nys_purge_key'] to be present in settings.php;
 * they are skipped gracefully when the key is absent (e.g. on developer
 * environments that have not yet added the key).
 *
 * @group cache_regression
 */
class CliCachePurgeTest extends CacheTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (empty(Settings::get('nys_purge_key', ''))) {
      $this->markTestSkipped(
        'nys_purge_key is not configured in settings.php. '
        . 'Add $settings[\'nys_purge_key\'] = \'<secret>\'; to settings.php and re-run.'
      );
    }
  }

  /**
   * A direct CLI-style $node->save() does NOT bust the Cloudflare cache.
   *
   * This is the known architectural limitation documented in tests/dtt/README.md:
   * pantheon_clear_edge_keys() is absent in CLI PHP context, so the BAN is
   * silently skipped. The page remains HIT at the CDN edge.
   *
   * If this test starts failing (CF shows MISS after a direct save), it
   * indicates that CLI BAN dispatch now works — update the comment and the
   * sister test accordingly.
   */
  public function testCliSaveDoesNotInvalidateCdnCache(): void {
    $node = $this->requireNodeByType('bill');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);

    // Direct API save — the CLI code path used by the importer.
    // Redis (Drupal page cache) is invalidated, but no BAN is dispatched.
    $node->save();

    // CF should still serve HIT because no BAN reached the CDN edge.
    $this->assertAnonymousCacheHit(
      $path,
      // phpcs:ignore
      'CF should still show HIT after a CLI-style save (no BAN dispatched). '
      . 'If this fails, CLI BAN dispatch now works and this test should be updated.'
    );
  }

  /**
   * POSTing to the purge endpoint after a CLI save dispatches the CF BAN.
   *
   * Full flow:
   *   warm → HIT → CLI save (CF still HIT) → POST purge endpoint → MISS → HIT.
   */
  public function testPurgeEndpointInvalidatesCdnCacheAfterCliSave(): void {
    $purge_key = Settings::get('nys_purge_key', '');

    $node = $this->requireNodeByType('bill');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    // Warm and confirm HIT.
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);

    // CLI-style save: Redis invalidated, CF cache untouched.
    $node->save();

    // Confirm CF is still HIT (no BAN yet).
    $this->assertAnonymousCacheHit($path);

    // Call the purge endpoint — runs in web-worker context, dispatches BAN.
    $response = $this->anonClient->post('/api/internal/cache/purge', [
      'headers' => [
        'Authorization' => 'Bearer ' . $purge_key,
        'Content-Type'  => 'application/json',
      ],
      'json' => ['tags' => ['node:' . $node->id()]],
    ]);
    $this->assertSame(200, $response->getStatusCode(),
      'Purge endpoint should return 200 for a valid authenticated request.');

    // CF BAN has now been dispatched — page should go MISS.
    $this->assertAnonymousCacheMiss($path);

    // Re-warm confirms the page is still publicly cacheable.
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * The purge endpoint returns 403 when called without a valid key.
   */
  public function testPurgeEndpointRejects403WithoutValidKey(): void {
    $response = $this->anonClient->post('/api/internal/cache/purge', [
      'headers' => [
        'Authorization' => 'Bearer invalid-key',
        'Content-Type'  => 'application/json',
      ],
      'json' => ['tags' => ['node:1']],
      'http_errors' => FALSE,
    ]);
    $this->assertSame(403, $response->getStatusCode());
  }

}
