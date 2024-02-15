<?php

namespace Drupal\stage_file_proxy\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\stage_file_proxy\DownloadManagerInterface;
use Drupal\stage_file_proxy\EventDispatcher\AlterExcludedPathsEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stage file proxy subscriber for controller requests.
 */
class StageFileProxySubscriber implements EventSubscriberInterface {

  /**
   * The manager used to fetch the file against.
   *
   * @var \Drupal\stage_file_proxy\DownloadManagerInterface
   */
  protected $manager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the FetchManager.
   *
   * @param \Drupal\stage_file_proxy\DownloadManagerInterface $manager
   *   The manager used to fetch the file against.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(DownloadManagerInterface $manager, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->manager = $manager;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Fetch the file from it's origin.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   *
   * @todo Drop GetRequestEvent typehint when dropping Drupal 9 support.
   */
  public function checkFileOrigin(RequestEvent|GetRequestEvent $event) {
    $config = $this->configFactory->get('stage_file_proxy.settings');

    // Get the origin server.
    $server = $config->get('origin');

    // Quit if no origin given.
    if (!$server) {
      return;
    }

    // Quit if we are the origin, ignore http(s).
    if (preg_replace('#^[a-z]*://#u', '', $server) === $event->getRequest()->getHost()) {
      return;
    }

    $file_dir = $this->manager->filePublicPath();
    $request_path = $event->getRequest()->getPathInfo();

    $request_path = mb_substr($request_path, 1);

    if (strpos($request_path, '' . $file_dir) !== 0) {
      return;
    }

    // Disallow directory traversal.
    if (in_array('..', explode('/', $request_path))) {
      return;
    }

    // Moving to parent directory is insane here, so prevent that.
    if (in_array('..', explode('/', $request_path))) {
      return;
    }

    // Quit if the extension is in the list of excluded extensions.
    $excluded_extensions = $config->get('excluded_extensions') ?
      array_map('trim', explode(',', $config->get('excluded_extensions'))) : [];

    $extension = pathinfo($request_path)['extension'] ?? '';
    if (in_array($extension, $excluded_extensions)) {
      return;
    }

    $alter_excluded_paths_event = new AlterExcludedPathsEvent([]);
    $this->eventDispatcher->dispatch($alter_excluded_paths_event, 'stage_file_proxy.alter_excluded_paths');
    $excluded_paths = $alter_excluded_paths_event->getExcludedPaths();
    foreach ($excluded_paths as $excluded_path) {
      if (strpos($request_path, $excluded_path) !== FALSE) {
        return;
      }
    }

    // Note if the origin server files location is different. This
    // must be the exact path for the remote site's public file
    // system path, and defaults to the local public file system path.
    $origin_dir = $config->get('origin_dir') ?? '';
    $remote_file_dir = trim($origin_dir);
    if ($remote_file_dir === '') {
      $remote_file_dir = $file_dir;
    }

    $request_path = rawurldecode($request_path);
    // Path relative to file directory. Used for hotlinking.
    $relative_path = mb_substr($request_path, mb_strlen($file_dir) + 1);
    // If file is fetched and use_imagecache_root is set, original is used.
    $paths = [$relative_path];

    // Webp support.
    $is_webp = FALSE;
    if (strpos($relative_path, '.webp')) {
      $paths[] = str_replace('.webp', '', $relative_path);
      $is_webp = TRUE;
    }

    foreach ($paths as $relative_path) {
      $fetch_path = $relative_path;

      // Don't touch CSS and JS aggregation. 'css/' and 'js/' are hard coded to
      // match route definitions.
      // @see \Drupal\system\Routing\AssetRoutes
      if (str_starts_with($relative_path, 'css/') || str_starts_with($relative_path, 'js/')) {
        return;
      }

      // Is this imagecache? Request the root file and let imagecache resize.
      // We check this first so locally added files have precedence.
      $original_path = $this->manager->styleOriginalPath($relative_path, TRUE);
      if ($original_path && !$is_webp) {
        if (file_exists($original_path)) {
          // Imagecache can generate it without our help.
          return;
        }
        if ($config->get('use_imagecache_root')) {
          // Config says: Fetch the original.
          $fetch_path = StreamWrapperManager::getTarget($original_path);
        }
      }

      $query = $this->requestStack->getCurrentRequest()->query->all();
      $query_parameters = UrlHelper::filterQueryParameters($query);
      $options = [
        'verify' => $config->get('verify'),
        'query' => $query_parameters,
      ];

      if ($config->get('hotlink')) {
        $location = Url::fromUri("$server/$remote_file_dir/$relative_path", [
          'query' => $query_parameters,
          'absolute' => TRUE,
        ])->toString();
        $response = new TrustedRedirectResponse($location);
        $response->addCacheableDependency($config);
        $event->setResponse($response);
      }
      elseif ($this->manager->fetch($server, $remote_file_dir, $fetch_path, $options)) {
        // Refresh this request & let the web server work out mime type, etc.
        $location = Url::fromUri('base://' . $request_path, [
          'query' => $query_parameters,
          'absolute' => TRUE,
        ])->toString();
        // Use default cache control: must-revalidate, no-cache, private.
        $event->setResponse(new RedirectResponse($location));
      }
    }
  }

  /**
   * Get the file URI without the extension from any conversion image style.
   *
   * If the image style converted the image, then an extension has been added
   * to the original file, resulting in filenames like image.png.jpeg.
   *
   * @param string $path
   *   The file path.
   *
   * @return string
   *   The file path without the extension from any conversion image style.
   *   Defaults to the $path when the $path does not have a double extension.
   *
   * @todo Use ImageStyleDownloadController method for a URI once https://www.drupal.org/project/drupal/issues/2786735 has been committed.
   * @todo this is used by #3402972 but caused regressions.
   */
  public static function getFilePathWithoutConvertedExtension(string $path): string {
    $original_path = $path;
    $original_path = '/' . ltrim($original_path, '/');
    $path_info = pathinfo($original_path);
    // Only convert the URI when the filename still has an extension.
    if (!empty($path_info['filename']) && pathinfo($path_info['filename'], PATHINFO_EXTENSION)) {
      $original_path = '';
      if (!empty($path_info['dirname']) && $path_info['dirname'] !== '.') {
        $original_path .= $path_info['dirname'] . DIRECTORY_SEPARATOR;
      }
      $original_path .= $path_info['filename'];
    }

    return str_starts_with($path, '/') ? $original_path : ltrim($original_path, '/');
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    // Priority 240 is after ban middleware but before page cache.
    $events[KernelEvents::REQUEST][] = ['checkFileOrigin', 240];
    return $events;
  }

}
