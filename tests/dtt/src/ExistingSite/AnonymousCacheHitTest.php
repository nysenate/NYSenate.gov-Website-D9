<?php

namespace Drupal\Tests\nys\ExistingSite;

/**
 * Verifies that anonymous page cache HITs are served correctly.
 *
 * All six top-level pages and all seven primary content type display pages
 * must return a cache HIT and declare cache-control: max-age=86400, public
 * on the second anonymous request.
 *
 * Non-invalidation (negative) cases live in AnonymousCacheNonInvalidationTest.
 * Cache MISS cases live in CacheMissInvalidationTest.
 *
 * @group cache_regression
 */
class AnonymousCacheHitTest extends CacheTestBase {

  // ---------------------------------------------------------------------------
  // Cache HITs
  // ---------------------------------------------------------------------------

  /**
   * All six top-level pages return a cache HIT and declare a 24-hour public
   * cache lifetime on the second anonymous request.
   *
   * The max-age assertion is folded in here rather than standing
   * alone because both properties are observable in the same warm GET request.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * All seven content type display pages return a cache HIT and declare a
   * 24-hour public cache lifetime on the second anonymous request.
   *
   * The max-age assertion is folded in here because both
   * properties are observable in the same warm GET request.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
    $this->assertCacheControlMaxAge($path, 86400);
  }

}
