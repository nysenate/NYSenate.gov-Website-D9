<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "json",
 *   title = @Translation("JSON")
 * )
 */
class Json extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Iterator over the JSON data.
   */
  protected ?\ArrayIterator $iterator = NULL;

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData(string $url): array {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    // Convert objects to associative arrays.
    $source_data = json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);

    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($source_data)) {
      $utf8response = mb_convert_encoding($response, 'UTF-8');
      $source_data = json_decode($utf8response, TRUE, 512, JSON_THROW_ON_ERROR);
    }

    // Backwards-compatibility for depth selection.
    if (is_int($this->itemSelector)) {
      return $this->selectByDepth($source_data);
    }

    // Otherwise, we're using xpath-like selectors.
    $selectors = explode('/', trim((string) $this->itemSelector, '/'));
    foreach ($selectors as $selector) {
      if (is_array($source_data) && array_key_exists($selector, $source_data)) {
        $source_data = $source_data[$selector];
      }
    }
    return $source_data;
  }

  /**
   * Get the source data for reading.
   *
   * @param array $raw_data
   *   Raw data from the JSON feed.
   *
   *   Selected items at the requested depth of the JSON feed.
   */
  protected function selectByDepth(array $raw_data): array {
    // Return the results in a recursive iterator that can traverse
    // multidimensional arrays.
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($raw_data),
      \RecursiveIteratorIterator::SELF_FIRST);
    $items = [];
    // Backwards-compatibility - an integer item_selector is interpreted as a
    // depth. When there is an array of items at the expected depth, pull that
    // array out as a distinct item.
    $identifierDepth = $this->itemSelector;
    $iterator->rewind();
    while ($iterator->valid()) {
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() == $identifierDepth) {
        $items[] = $item;
      }
      $iterator->next();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl(string $url): bool {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $field_data = $current;
        $field_selectors = explode('/', trim((string) $selector, '/'));
        foreach ($field_selectors as $field_selector) {
          if (is_array($field_data) && array_key_exists($field_selector, $field_data)) {
            $field_data = $field_data[$field_selector];
          }
          else {
            $field_data = '';
          }
        }
        $this->currentItem[$field_name] = $field_data;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }

}
