<?php

namespace Drupal\nys_openleg_imports;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\ResponseUpdate;
use Drupal\nys_openleg_api\Request;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\ResponseSearch;
use Drupal\nys_openleg_api\Service\Api;
use Drupal\nys_openleg_imports\Service\OpenlegImportProcessorManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Openleg importer plugins.
 */
abstract class ImporterBase implements ImporterInterface {

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected array $config;

  /**
   * The Openleg API Manager service.
   *
   * @var \Drupal\nys_openleg_api\Service\Api
   */
  protected Api $apiManager;

  /**
   * Drupal EntityType Manager service.
   *
   * @var \Drupal\nys_openleg_imports\Service\OpenlegImportProcessorManager
   */
  protected OpenlegImportProcessorManager $processorManager;

  /**
   * A logger pre-configured for this plugin type.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * An Openleg API Request instance.
   *
   * @var \Drupal\nys_openleg\Api\RequestPluginInterface
   */
  protected RequestPluginInterface $requester;

  /**
   * The results of the most recent operation.
   *
   * @var \Drupal\nys_openleg_imports\ImportResult
   */
  protected ImportResult $results;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_openleg_api\Service\Api $api_manager
   *   Openleg API Manager service.
   * @param \Drupal\nys_openleg_imports\Service\OpenlegImportProcessorManager $processorManager
   *   Drupal EntityType Manager service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   Openleg Import pre-configured logging channel.
   * @param array $plugin_definition
   *   The plugin's definition.
   * @param string $plugin_id
   *   The plugin's name.
   * @param array $configuration
   *   The plugin's configuration.
   */
  public function __construct(ApiManager $api_manager, OpenlegImportProcessorManager $processorManager, LoggerChannel $logger, array $plugin_definition, string $plugin_id, array $configuration = []) {
    $this->definition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->config = $configuration;
    $this->apiManager = $api_manager;
    $this->processorManager = $processorManager;
    $this->logger = $logger;
    $this->requester = $this->apiManager->getRequest($this->definition['requester']);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('openleg_api'),
      $container->get('manager.openleg_import_processors'),
      $container->get('openleg_imports.logger'),
      $plugin_definition,
      $plugin_id,
      $configuration,
    );
  }

  /**
   * {@inheritDoc}
   */
  public function importUpdates(string $time_from, string $time_to): ImportResult {
    /**
     * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate $updates
     */
    $updates = $this->requester->retrieveUpdates($time_from, $time_to);
    return $this->import($updates->listIds());
  }

  /**
   * {@inheritDoc}
   */
  public function importItem(string $name): ImportResult {
    return $this->import([$name]);
  }

  /**
   * Get the plugin's result collector, instantiating one if necessary.
   *
   * @param bool $new
   *   If a new result collector should be made even if one exists.
   *
   * @return \Drupal\nys_openleg_imports\ImportResult
   *   A result collector.
   */
  protected function getResult(bool $new = TRUE): ImportResult {
    if ($new || !isset($this->results)) {
      $this->results = new ImportResult();
    }
    return $this->results;
  }

  /**
   * Generates a processor plugin instance matched to this importer.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getProcessor(): ImportProcessorBase {
    return $this->processorManager->createInstance($this->pluginId, $this->config);
  }

  /**
   * Gets the name of the requester this importer uses.
   */
  public function getRequester(): string {
    return $this->definition['requester'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function import(array $items): ImportResult {
    // Init and report.
    $this->results = $this->getResult();
    $this->logger->info(
          "[@time] Beginning processing of @total @type items", [
            '@total' => count($items),
            '@type' => $this->pluginId,
            '@time' => date(Request::OPENLEG_TIME_SIMPLE, time()),
          ]
      );

    // Iterate the items.  Success/fail/exceptions are tracked.
    foreach ($items as $item_name) {
      try {
        $full_item = $this->requester->retrieve($item_name);
        $processor = $this->getProcessor()->init($full_item);
        if (!$full_item->success()) {
          $this->logger->error(
                'API call to retrieve @name failed', [
                  '@name' => $item_name,
                  '@response' => var_export($full_item, 1),
                ]
            );
        }
        $success = $full_item->success() && $processor->process();
      }
      catch (\Throwable $e) {
        $success = FALSE;
        $this->results->addException($e->getMessage());
        $this->logger->error(" ! EXCP: @msg", ['@msg' => $e->getMessage()]);
      }

      if ($success) {
        $this->results->addSuccess();
        $this->logger->info(" - @name imported successfully.", ['@name' => $item_name]);
      }
      else {
        $this->results->addFail();
        $this->logger->error(" - @name import failed.", ['@name' => $item_name]);
      }
    }

    // Log the result.
    $this->logResults($items);

    return $this->results;
  }

  /**
   * Creates a watchdog entry for the import results.
   */
  public function logResults(array $items): void {
    $attempted = count($items);
    $params = [
      '@total' => $attempted,
      '@type' => $this->pluginId,
      '@success' => $this->results->getSuccess(),
      '@fail' => $this->results->getFail(),
      '@skip' => $attempted - $this->results->total(),
      '@time' => date(Request::OPENLEG_TIME_SIMPLE, time()),
    ];
    $message = "[@time] Finished processing @total @type items: @success pass, @fail fail, @skip skipped";
    if ($full_fail = count($this->results->getExceptions())) {
      $message .= " ($full_fail exceptions)";
      $type = 'error';
    }
    else {
      $type = $this->results->getFail() ? 'warning' : 'info';
    }
    $this->logger->$type($message, $params);
  }

  /**
   * Given an Openleg search response, returns a unique array of IDs.
   */
  public function getIdFromSearchList(ResponseSearch $response): array {
    return array_unique(
          array_filter(
              array_map(
                  function ($v) {
                        return $this->id($v->result);
                  },
                  $response->items()
              )
          )
      );
  }

  /**
   * Generates an array of IDs from a list of calendars in a calendar year.
   */
  public function getIdFromYearList(ResponseSearch $response): array {
    return array_unique(
          array_filter(
              array_map(
                  [$this, 'id'],
                  $response->items()
              )
          )
      );
  }

  /**
   * Gets the unique OpenLeg ID for a list item.
   *
   * @param object $item
   *   An item from an OpenLeg "list"-style response.
   *
   * @return string
   *   The unique ID, suitable for the "name" portion of a request.
   */
  abstract public function id(object $item): string;

}
