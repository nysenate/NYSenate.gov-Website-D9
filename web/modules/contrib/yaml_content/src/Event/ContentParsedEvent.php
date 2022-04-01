<?php

namespace Drupal\yaml_content\Event;

use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content content parsed event for event listeners.
 */
class ContentParsedEvent extends EventBase {

  /**
   * The full file path for the file that was parsed.
   *
   * @var string
   */
  protected $contentFile;

  /**
   * The parsed content from the file prepared for import.
   *
   * @var array
   */
  protected $parsedContent;

  /**
   * Constructs a yaml content content parsed event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param string $content_file
   *   The full file path for the content file that was parsed.
   * @param array $parsed_content
   *   The parsed content loaded from the content file.
   */
  public function __construct(ContentLoaderInterface $loader, $content_file, array $parsed_content) {
    parent::__construct($loader);

    $this->contentFile = $content_file;
    $this->parsedContent = $parsed_content;
  }

  /**
   * Gets the file path of the content file that was loaded.
   *
   * @return string
   *   The file path for the content file that was loaded.
   */
  public function getContentFile() {
    return $this->contentFile;
  }

  /**
   * Gets the parsed content loaded from the content file.
   *
   * @return array
   *   The parsed content structure interpreted from the content file.
   */
  public function getParsedContent() {
    return $this->parsedContent;
  }

}
