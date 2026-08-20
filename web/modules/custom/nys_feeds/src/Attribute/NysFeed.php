<?php

namespace Drupal\nys_feeds\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the NysFeed attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class NysFeed extends Plugin {

  /**
   * {@inheritDoc}
   *
   * Constructs a NysFeed attribute.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $label
   *   The simple text label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $description
   *   A brief description of the plugin.  Will be the same as $label if
   *   not provided.
   * @param string $entity_type
   *   Used with pre-built query functionality.
   * @param string $bundle
   *   Used with pre-built query functionality.
   * @param bool $cacheable
   *   Indicates if caching metadata will be attached to the response.  Also,
   *   JsonResponse vs. CacheableJsonResponse.
   * @param int $max_cache_age
   *   Maximum cache age in seconds, defaults to 7200.
   * @param bool $private
   *   Indicates if the feed should be omitted from the public list of feeds.
   * @param array $params
   *   An array of query string parameters recognized by the plugin.  Should be
   *   in the form ['param_name' => 'default value', ...].  The default value
   *   must be NULL if the parameter is optional.
   * @param array ...$base
   *   For the parent parameters.
   */
  public function __construct(
    public TranslatableMarkup|string $label,
    public TranslatableMarkup|string $description = '',
    public string $entity_type = '',
    public string $bundle = '',
    public bool $cacheable = TRUE,
    public int $max_cache_age = 7200,
    public bool $private = FALSE,
    public array $params = [],
    mixed ...$base,
  ) {
    // Call the base constructor.
    parent::__construct(...$base);

    // Ensure $description is populated with at least the $label.
    if (!$description) {
      $this->description = $label;
    }
  }

}
