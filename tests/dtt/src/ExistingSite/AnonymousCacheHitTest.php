<?php

namespace Drupal\Tests\nys\ExistingSite;

/**
 * Verifies that anonymous page cache HITs are served correctly.
 *
 * All six top-level pages and all seven primary content type display pages
 * must return a cache HIT and declare cache-control: max-age=2592000, public
 * on the second anonymous request.
 *
 * Open Legislation browse pages (top-level, by-type, and by-statute) are also
 * covered here; /legislation/laws/search/ is deliberately excluded because
 * that route keeps no_cache: TRUE.
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
   * Top-level pages return cache HITs with a 30-day public lifetime.
   *
   * The max-age assertion is folded in here rather than standing
   * alone because both properties are observable in the same warm GET request.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    // Use the combined method to fold the HIT and max-age checks into a single
    // request, reducing per-page traffic and avoiding CF rate-limit bursts.
    $this->assertAnonymousCacheHitWithMaxAge($path, 2592000);
  }

  /**
   * Content display pages return cache HITs with a 30-day public lifetime.
   *
   * The max-age assertion is folded in here because both
   * properties are observable in the same warm GET request.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithMaxAge($path, 2592000);
  }

  // ---------------------------------------------------------------------------
  // Open Legislation browse pages
  // ---------------------------------------------------------------------------

  /**
   * Data provider for the three stable Open Legislation browse paths.
   *
   * The top-level, a Penal Law listing, and a specific section are chosen
   * because they exist on every environment and do not require any database
   * content beyond the module's configuration. The search path is excluded
   * because its route retains no_cache: TRUE.
   */
  public static function openLegBrowsePageProvider(): array {
    return self::asProvider([
      '/legislation/laws',
      '/legislation/laws/PEN',
      '/legislation/laws/PEN/125.10',
    ]);
  }

  /**
   * OpenLeg browse pages return cache HITs with a seven-day public lifetime.
   *
   * @dataProvider openLegBrowsePageProvider
   */
  public function testOpenLegBrowsePageCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithMaxAge($path, 604800);
  }

}
