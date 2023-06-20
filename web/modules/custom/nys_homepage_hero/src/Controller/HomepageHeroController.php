<?php

namespace Drupal\nys_homepage_hero\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;

/**
 * Class HomepageHeroController.
 *
 * Handles routing for nys_homepage_hero module.
 */
class HomepageHeroController extends ControllerBase {

  /**
   * Returns the value of the homepage_hero_session_in_progress variable.
   */
  public function homepageHeroStatus() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $response = new HtmlResponse(\Drupal::state()->get('homepage_hero_session_in_progress'));
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * Session has begun. Signal to polling clients to reload the page.
   */
  public static function homepageHeroAddItem() {
    \Drupal::state()->set('homepage_hero_session_in_progress', 1);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['views:homepage_hero']);
  }

  /**
   * Session has ended. Reload the page causing the polling JS to be removed.
   */
  public static function homepageHeroRemoveItem() {
    \Drupal::state()->set('homepage_hero_add_polling_js', 0);
    \Drupal::state()->set('homepage_hero_session_in_progress', 0);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(
          [
            'views:homepage_hero',
          ]
      );
  }

}
