<?php

namespace Drupal\nys_openleg\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Limits shared freshness for OpenLeg browse responses only.
 */
final class OpenLegCacheControlSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the OpenLeg cache control subscriber.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Supplies an OpenLeg shared lifetime before http_cache_control's default.
   */
  public function onRespond(ResponseEvent $event): void {
    if (!$event->isMainRequest()
      || !in_array($event->getRequest()->attributes->get('_route'), [
        'nys_openleg.top',
        'nys_openleg.book',
        'nys_openleg.location',
      ], TRUE)) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface
      || !$response->isSuccessful()
      || !$response->isCacheable()
      || !$response->headers->hasCacheControlDirective('public')
      || $response->headers->hasCacheControlDirective('private')
      || $response->headers->hasCacheControlDirective('no-cache')
      || $response->headers->hasCacheControlDirective('no-store')) {
      return;
    }

    $metadataMaxAge = $response->getCacheableMetadata()->getCacheMaxAge();
    $sharedCeiling = (int) $this->configFactory
      ->get('http_cache_control.settings')
      ->get('cache.http.s_maxage');
    if ($metadataMaxAge <= 0 || $sharedCeiling <= 0) {
      return;
    }

    $sharedMaxAge = min($metadataMaxAge, $sharedCeiling);
    $existingSharedMaxAge = $response->headers->getCacheControlDirective('s-maxage');
    if ($existingSharedMaxAge !== NULL) {
      if (!is_string($existingSharedMaxAge) || !ctype_digit($existingSharedMaxAge)) {
        return;
      }
      $sharedMaxAge = min($sharedMaxAge, (int) $existingSharedMaxAge);
    }

    if ($sharedMaxAge > 0) {
      $response->headers->addCacheControlDirective('s-maxage', $sharedMaxAge);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After core/override (0), before http_cache_control (-10).
    return [KernelEvents::RESPONSE => ['onRespond', -5]];
  }

}
