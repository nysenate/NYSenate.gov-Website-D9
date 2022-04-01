<?php

namespace Drupal\yaml_content\Plugin;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * The contextual data for content being actively loaded.
 */
class ProcessingContext {

  /**
   * The field currently being processed.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * The active content loader instance.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   */
  protected $contentLoader;

  /**
   * Set the field context.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field currently being processed.
   */
  public function setField(FieldItemListInterface $field) {
    $this->field = $field;
  }

  /**
   * Get the field context.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field currently being processed.
   */
  public function getField() {
    if (!isset($this->field)) {
      // @todo Impelment a more specific exception.
      throw new \Exception('Missing field context.');
    }
    return $this->field;
  }

  /**
   * Set the content loader context.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $contentLoader
   *   The content loader instance actively loading content.
   */
  public function setContentLoader(ContentLoaderInterface $contentLoader) {
    $this->contentLoader = $contentLoader;
  }

  /**
   * Get the content loader context.
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   *   The content loader instance actively loading content.
   */
  public function getContentLoader() {
    if (!isset($this->contentLoader)) {
      // @todo Impelment a more specific exception.
      throw new \Exception('Missing content loader context.');
    }
    return $this->contentLoader;
  }

}
