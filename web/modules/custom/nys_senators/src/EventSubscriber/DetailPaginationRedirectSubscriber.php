<?php

namespace Drupal\nys_senators\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Retires detail pagination after routing and access checks, before rendering.
 */
final class DetailPaginationRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the detail pagination subscriber.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Redirects only detail bundles whose sole pager was article_footer.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    // Exit early unless this is an anonymous detail request with a page query.
    if (!$event->isMainRequest() || !$this->currentUser->isAnonymous()
      || !in_array($request->getMethod(), ['GET', 'HEAD'], TRUE)
      || $request->attributes->get('_route') !== 'entity.node.canonical'
      || !$request->query->has('page')) {
      return;
    }

    // Use the routed node and limit redirects to the shared footer's bundles.
    $node = $request->attributes->get('node');
    if (!$node instanceof NodeInterface
      || !in_array($node->bundle(), ['article', 'in_the_news', 'event', 'honoree'], TRUE)) {
      return;
    }

    // Require view access and confirm the footer no longer uses pagination.
    $access = $node->access('view', $this->currentUser, TRUE);
    $view = $this->configFactory->get('views.view.senator_microsite_content');
    if (!$access->isAllowed()
      || $view->get('display.article_footer.display_options.pager.type') !== 'some') {
      return;
    }

    // Keep unrelated queries on the canonical URL, without target overrides.
    $query = $request->query->all();
    unset($query['page'], $query['destination']);
    $url = $node->toUrl('canonical', ['query' => $query, 'absolute' => FALSE])->toString(TRUE);

    // Track dependencies and URL variation so cached redirects stay valid.
    $performance = $this->configFactory->get('system.performance');
    $maxAge = (int) $performance->get('cache.page.max_age');
    $metadata = (new CacheableMetadata())
      ->setCacheMaxAge($maxAge)
      ->addCacheableDependency($node)
      ->addCacheableDependency($access)
      ->addCacheableDependency($view)
      ->addCacheableDependency($performance)
      ->addCacheableDependency($url)
      ->addCacheContexts(['url.path', 'url.query_args']);

    // Return a cacheable local 301 instead of rendering the detail page.
    $response = new LocalRedirectResponse($url->getGeneratedUrl(), 301);
    $response->addCacheableDependency($metadata);
    $response->setPublic();
    $response->setMaxAge($maxAge);

    // Prevent core's response subscriber from replacing the redirect target.
    $request->query->remove('destination');
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['onRequest', 31]];
  }

}
