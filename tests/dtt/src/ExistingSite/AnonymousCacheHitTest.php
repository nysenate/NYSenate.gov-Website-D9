<?php

namespace Drupal\Tests\nys\ExistingSite;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Verifies that anonymous page cache HITs are served correctly.
 *
 * Public pages must return a cache HIT with a browser max-age of 300 seconds
 * and the global shared-cache s-maxage, except for OpenLeg browse responses.
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
   * Top-level pages return cache HITs with split browser/shared lifetimes.
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
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 2592000, 300, 2592000);
  }

  /**
   * Content display pages return cache HITs with split browser/shared lifetimes.
   *
   * The max-age assertion is folded in here because both
   * properties are observable in the same warm GET request.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 2592000, 300, 2592000);
  }

  /**
   * Positive Views lifetimes do not shorten the global shared HTTP lifetime.
   */
  public function testTimedViewPageUsesGlobalSharedLifetime(): void {
    $path = $this->requireTermByVocabulary('committees')->toUrl()->toString();
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 2592000, 300, 2592000);
  }

  /**
   * Issue pages link to senator lookup without embedding an uncacheable login.
   */
  public function testIssuePageCacheHitWithFindSenatorLink(): void {
    $path = $this->requireTermByVocabulary('issues')->toUrl()->toString();
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 2592000, 300, 2592000);
    for ($attempt = 0; $attempt < 2; $attempt++) {
      $response = $this->anonClient->get($path);
      $this->assertSame(200, $response->getStatusCode());
      $crawler = new Crawler((string) $response->getBody());
      $link = $crawler->filter('.taxonomy-issues__header-cta a.c-issue--contact-btn');
      $this->assertCount(1, $link);
      $this->assertSame('/find-my-senator', $link->attr('href'));
      $this->assertSame('Find your Senator', trim($link->text()));
      $this->assertCount(0, $crawler->filter('.user-login-modal, form.user-login-form, .ctools-modal-login-modal'));
    }
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
   * Successful OpenLeg pages respect their seven-day render-cache lifetime.
   *
   * @dataProvider openLegBrowsePageProvider
   */
  public function testOpenLegBrowsePageCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 604800);
  }

  /**
   * OpenLeg's short-lived not-found render stays short in both cache layers.
   */
  public function testOpenLegShortLifetimePageCacheHit(): void {
    $path = '/legislation/laws/NYS_CACHE_TEST_NOT_A_REAL_STATUTE';
    $this->warmCache($path);
    $this->assertAnonymousCacheHitWithCacheControl($path, 300, 300, 300, 300);
  }

  /**
   * Provides search forms and result variants, including empty searches.
   */
  public static function uncacheableSearchPathProvider(): array {
    return self::asProvider([
      '/legislation/laws/search',
      '/search/global/result',
      '/search/global/result?full_text=test&senator=all&committee=all&type=all&sort_order=DESC',
      '/search/legislation',
      '/search/legislation?type=bill&status=all&sponsor=all&full_text=license&committee=all',
      '/search/legislation?type=transcript&full_text=license',
    ]);
  }

  /**
   * Search forms and results remain uncacheable on repeated requests.
   *
   * @dataProvider uncacheableSearchPathProvider
   */
  public function testSearchRemainsUncacheable(string $path): void {
    for ($attempt = 0; $attempt < 2; $attempt++) {
      $response = $this->anonClient->get($path);
      $this->assertSame(200, $response->getStatusCode());
      $cacheControl = strtolower($response->getHeaderLine('cache-control'));

      $this->assertStringContainsString('no-cache', $cacheControl, $path);
      $this->assertStringNotContainsString('public', $cacheControl, $path);
      $this->assertNull($this->cacheControlDirectiveValue($cacheControl, 's-maxage'), $path);
      $this->assertLessThanOrEqual(0, $this->cacheControlDirectiveValue($cacheControl, 'max-age') ?? 0, $path);
      foreach (['x-drupal-cache', 'x-drupal-dynamic-cache'] as $name) {
        if ($response->hasHeader($name)) {
          $this->assertStringStartsWith('UNCACHEABLE', $response->getHeaderLine($name), $path);
        }
      }
      if ($response->hasHeader('cf-cache-status')) {
        $this->assertNotSame('HIT', strtoupper(trim($response->getHeaderLine('cf-cache-status'))), $path);
      }
    }
  }

  /**
   * Login form responses remain private and are not made publicly cacheable.
   */
  public function testLoginFormRemainsPrivate(): void {
    $response = $this->anonClient->get('/user/login');
    $cacheControl = strtolower($response->getHeaderLine('cache-control'));

    $this->assertStringContainsString('private', $cacheControl);
    $this->assertStringNotContainsString('public', $cacheControl);
    $this->assertStringContainsString('no-cache', $cacheControl);
    $this->assertLessThanOrEqual(0, $this->cacheControlDirectiveValue($cacheControl, 'max-age') ?? 0);
    $this->assertNull($this->cacheControlDirectiveValue($cacheControl, 's-maxage'));
  }

  /**
   * Special 404 responses do not receive a positive shared-cache lifetime.
   */
  public function testNotFoundResponseDoesNotGetSharedTtl(): void {
    $response = $this->anonClient->get('/cache-control-policy-not-found', [
      'http_errors' => FALSE,
    ]);
    $this->assertSame(404, $response->getStatusCode());

    $cacheControl = strtolower($response->getHeaderLine('cache-control'));
    $sharedMaxAge = $this->cacheControlDirectiveValue($cacheControl, 's-maxage');
    $browserMaxAge = $this->cacheControlDirectiveValue($cacheControl, 'max-age');
    $this->assertLessThanOrEqual(0, $sharedMaxAge ?? 0);
    $this->assertLessThanOrEqual(300, $browserMaxAge ?? 0);
  }

}
