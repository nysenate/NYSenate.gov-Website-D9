<?php

namespace Drupal\stage_file_proxy\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\stage_file_proxy\EventDispatcher\AlterExcludedPathsEvent;
use Drupal\stage_file_proxy\FetchManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stage file proxy subscriber for controller requests.
 *
 * @deprecated in stage_file_proxy:2.1.0 and is removed from stage_file_proxy:3.0.0.
 *   Use StageFileProxySubscriber instead.
 *
 * @see https://www.drupal.org/project/stage_file_proxy/issues/3282542
 */
class ProxySubscriber implements EventSubscriberInterface {

  /**
   * The manager used to fetch the file against.
   *
   * @var \Drupal\stage_file_proxy\FetchManagerInterface
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
   * @param \Drupal\stage_file_proxy\FetchManagerInterface $manager
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
  public function __construct(FetchManagerInterface $manager, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->manager = $manager;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Fetch the file from it's origin.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function checkFileOrigin(RequestEvent $event) {
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

    $path_info = pathinfo($request_path);
    $ext = $path_info['extension'];

    if (in_array($ext, $excluded_extensions)) {
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
    $relative_path = mb_substr($request_path, mb_strlen($file_dir));
    // If file is fetched and use_imagecache_root is set, original is used.
    $paths = [$relative_path];

    // Webp support.
    if (str_ends_with($relative_path, '.webp')) {
      $paths[] = str_replace('.webp', '', $relative_path);
      $paths = array_reverse($paths);
    }

    foreach ($paths as $relative_path) {
      $fetch_path = $relative_path;

      // Is this imagecache? Request the root file and let imagecache resize.
      // We check this first so locally added files have precedence.
      $original_path = $this->manager->styleOriginalPath($relative_path, TRUE);
      if ($original_path) {
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
        'headers' => $this->createProxyHeadersArray($config->get('proxy_headers')),
      ];

      if ($config->get('hotlink')) {

        $location = Url::fromUri("$server/$remote_file_dir/$relative_path", [
          'query' => $query_parameters,
          'absolute' => TRUE,
        ])->toString();

      }
      elseif ($this->manager->fetch($server, $remote_file_dir, $fetch_path, $options)) {
        // Refresh this request & let the web server work out mime type, etc.
        $location = Url::fromUri('base://' . $request_path, [
          'query' => $query_parameters,
          'absolute' => TRUE,
        ])->toString();
        // Avoid redirection caching in upstream proxies.
        header("Cache-Control: must-revalidate, no-cache, post-check=0, pre-check=0, private");
      }

      if (isset($location)) {
        header("Location: $location");
        exit;
      }
    }
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

  /**
   * Helper function to generate HTTP headers array.
   *
   * @param string $headers_string
   *   Header string to break apart.
   *
   * @return array
   *   Any array for proxy headers.
   */
  protected function createProxyHeadersArray(string $headers_string) {
    $lines = explode("\n", $headers_string);
    $headers = [];
    foreach ($lines as $line) {
      $header = explode('|', $line);
      if (count($header) > 1) {
        $headers[$header[0]] = $header[1];
      }
    }
    return $headers;
  }

}
