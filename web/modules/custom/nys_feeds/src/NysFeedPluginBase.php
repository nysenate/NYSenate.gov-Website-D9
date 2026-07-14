<?php

namespace Drupal\nys_feeds;

use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for NYS Feeds plugins.
 */
abstract class NysFeedPluginBase implements NysFeedPluginInterface {

  /**
   * Drupal's Date Formatter Service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Instantiation configuration.
   *
   * @var array
   */
  protected array $config;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * The calculated time zone for the request (current user, or system default).
   *
   * @var string
   */
  protected string $timezone;

  /**
   * The default timezone to use, just in case.
   */
  protected string $defaultTimezone = 'America/New_York';

  /**
   * The state monitor for the current request.
   *
   * @var \Drupal\nys_feeds\FeedState
   */
  protected FeedState $state;

  /**
   * Constructor.
   */
  public function __construct(array $definition, array $config = []) {
    $this->definition = $definition;
    $this->config = $config;
    $this->dateFormatter = \Drupal::service('date.formatter');
    $this->timezone = \Drupal::currentUser()->getTimeZone()
      ?? \Drupal::config('system.date')->get('timezone.default');
    $this->state = $this->config['state'] ?? new FeedState($this->config['params'] ?? []);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($plugin_definition, $configuration);
  }

  /**
   * Transforms parameters in ::$state as needed by the plugin.  Chainable.
   */
  protected function resolveParams(): self {
    return $this;
  }

  /**
   * Override in the plugin to fetch the relevant data.
   *
   * @return array
   *   Array of results.
   */
  protected function query(): array {
    return [];
  }

  /**
   * Transcribes a single entry into a JSON-ready array.
   */
  protected function transcribeEntry(mixed $data): mixed {
    return $data;
  }

  /**
   * {@inheritDoc}
   *
   * Can optionally pass in a new FeedState to start fresh.
   */
  public function getFeed(?FeedState $state = NULL): FeedState {
    if ($state) {
      $this->state = $state;
    }

    // Detect the parameters and compile the results.
    $data = $this->resolveParams()->query();

    foreach ($data as $key => $val) {
      $state->data[$key] = $this->transcribeEntry($val);
    }
    if (!count($data)) {
      $state->messages[] = "No data found";
    }

    return $state;
  }

  /**
   * Renders an epoch timestamp as a date/time string, considering timezone.
   *
   * @param int $date
   *   An epoch timestamp.
   * @param bool $include_time
   *   If TRUE, a time portion will be included in the return.
   */
  protected function formatDate(int $date, bool $include_time = TRUE): string {
    $format = 'Y-m-d' . ($include_time ? '\TH:i:s' : '');
    try {
      $ret = $this->dateFormatter->format(
        $date,
        'custom',
        $format,
        $this->timezone ?? $this->defaultTimezone
      );
    }
    catch (\Exception) {
      $ret = 'Failed to parse date';
    }
    return $ret;
  }

  /**
   * Retrieve the internal URL for a content entity.
   */
  protected function getUrl(ContentEntityInterface $entity): string {
    try {
      $url = $entity->toUrl()->toString();
    }
    catch (\Exception) {
      $url = 'Error rendering URL';
    }
    return $url;
  }

  /**
   * Forms a reasonable array based on a location field.
   *
   * If anything can't be resolved, an empty array is returned.
   */
  protected function getLocation(AddressFieldItemList $location_field): array {
    // Collect the address fields.
    try {
      /** @var \Drupal\address\AddressInterface $item */
      $item = $location_field->first();
      $item_array = $item->toArray() ?? [];
      $location = array_map(
        fn($val) => $val ?? '',
        $item_array
      );
    }
    catch (\Exception) {
      $location = [];
    }
    return $location;
  }

  /**
   * Gets an array of labels from an entity reference field.
   */
  protected function getReferencedLabels(EntityReferenceFieldItemList $field): array {
    // Compile issues.
    return array_filter(array_map(
      fn($entity) => $entity->label(),
      $field->referencedEntities() ?? []
    ));
  }

  /**
   * Gets the standard media fields, if available.
   */
  protected function getMediaFields(ContentEntityInterface $entity): array {
    $fields = [
      'teleconference_id' => 'field_teleconference_id_number',
      'teleconference_number' => 'field_teleconference_number',
      'ustream_id' => 'field_ustream',
      'video_redirect' => 'field_video_redirect',
      'video_status' => 'field_video_status',
      'yt_archive_id' => 'field_yt',
    ];
    foreach ($fields as $key => $val) {
      try {
        $field = $entity->get($val);
      }
      catch (\Exception) {
        $field = NULL;
      }
      if ($field) {
        $ret[$key] = $val->value ?? '';
      }
    }
    return $ret;
  }

}
