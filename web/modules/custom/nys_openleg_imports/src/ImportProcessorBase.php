<?php

namespace Drupal\nys_openleg_imports;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Openleg Import Processor plugins.
 */
abstract class ImportProcessorBase implements ImportProcessorInterface {

  /**
   * Drupal EntityType Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The plugin definition.
   *
   * @var mixed
   */
  protected $definition;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected array $configuration;

  /**
   * The Openleg ResponseItem to be processed.
   *
   * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem
   */
  protected ResponseItem $item;

  /**
   * Local cache of resolved nodes, keyed by the serialized search array.
   *
   * @var array
   */
  protected array $nodes = [];

  /**
   * A logger pre-configured for this plugin type.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannel $logger, $plugin_definition, $plugin_id, array $configuration) {
    $this->entityTypeManager = $entityTypeManager;
    $this->definition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('logger.channel.openleg_imports'),
          $plugin_definition,
          $plugin_id,
          $configuration
      );
  }

  /**
   * {@inheritDoc}
   */
  public function init(ResponseItem $item): self {
    $this->item = $item;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function process(): bool {
    $ret = FALSE;
    if ($node = $this->resolveNode()) {
      try {
        $ret = $this->transcribeToNode($this->item->result(), $node);
        if ($ret) {
          $node->save();
        }
      }
      catch (\Throwable $e) {
        $ret = FALSE;
        $this->logger->error(" ! EXCP: @msg", ['@msg' => $e->getMessage()]);
      }
    }
    return $ret;
  }

  /**
   * Resolves the Node entity to which this item will be transcribed.
   *
   * If a matching node cannot be found, a new *unsaved* node is created.  The
   * exceptions can be thrown from the entity manager when creating a node.  In
   * that event, processing cannot continue, so we let it break.
   *
   * The 'type' key in $params is normally set per the processor plugin
   * definition.  It is allowed to be overridden by the caller due to bills
   * and resolutions using the same OL call, but requiring different bundles.
   *
   * @param array $params
   *   Additional properties used to filter the search.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The resolved or created node.
   *
   * @todo Separate resolutions into its own processor.
   */
  protected function resolveNode(array $params = []): ?Node {
    $search = $this->constructSearchArray($params) + ['type' => $this->definition['bundle']];
    $key = serialize($search);
    if (!($this->nodes[$key] ?? FALSE)) {
      try {
        $nodes = $this->entityTypeManager->getStorage('node')
          ->loadByProperties($search);
      }
      catch (\Throwable $e) {
        $nodes = [];
      }

      if (!($node = reset($nodes))) {
        try {
          $node = $this->entityTypeManager->getStorage('node')->create($search);
        }
        catch (\Throwable $e) {
          $this->logger->error(
                'Failed to create a new node for @type import',
                ['@type' => $search['type'], '@message' => $e->getMessage()]
            );
          $node = NULL;
        }
      }
      $this->nodes[$key] = $node;
    }

    return $this->nodes[$key];
  }

  /**
   * Constructs a key-value array appropriate to use in loadByProperties().
   *
   * Note that 'type' will be set by the annotated bundle.  This method is
   * responsible for providing any other key requirements.
   *
   * @param array $params
   *   An array of properties to search beyond the default title.  Note that
   *   this can override the default title.
   *
   * @return array
   *   In the form [<field_name> => <exact_value>, ...]
   *
   * @see \Drupal\Core\Entity\EntityStorageBase::loadByProperties()
   */
  protected function constructSearchArray(array $params = []): array {
    return $params + ['title' => $this->getId()];
  }

  /**
   * Given an Openleg item response, returns the unique ID.
   */
  abstract public function getId(): string;

}
