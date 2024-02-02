<?php

namespace Drupal\name;

/**
 * Provides an interface defining a name formatter.
 */
interface NameFormatterInterface {

  /**
   * Formats an array of name components.
   *
   * @param array $components
   *   An array of name components to format.
   *
   *   The expected components are:
   *   - title (string)
   *   - given (string)
   *   - middle (string)
   *   - family (string)
   *   - generational (string)
   *   - credentials (string)
   *   The following tokens are also supported:
   *   - preferred (string)
   *   - alternative (string)
   * @param string $type
   *   (optional) The name format type to load. If the format does not exist,
   *   the 'default' format is used.
   *   Defaults to 'default'.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   A renderable object representing the name.
   */
  public function format(array $components, $type = 'default', $langcode = NULL);

  /**
   * Formats a list of author information.
   *
   * @param array $items
   *   A nested array of name components to format.
   * @param string $type
   *   (optional) The name format type to load. If the format does not exist,
   *   the 'default' format is used.
   *   Defaults to 'default'.
   * @param string $list_type
   *   (optional) The list format type to load. If the format does not exist,
   *   the 'default' format is used.
   *   Defaults to 'default'.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The processed name in a MarkupInterface object.
   */
  public function formatList(array $items, $type = 'default', $list_type = 'default', $langcode = NULL);

  /**
   * Sets the value of a setting for the formatter.
   *
   * @param string $key
   *   The setting name.
   * @param mixed $value
   *   The setting value.
   *
   * @return \Drupal\name\NameFormatterInterface
   *   The name formatter instance.
   */
  public function setSetting($key, $value);

  /**
   * Gets the value of a setting for the formatter.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The value of the setting or NULL if not found.
   */
  public function getSetting($key);

  /**
   * Defines the supported final delimitor options.
   *
   * @param bool $include_examples
   *   Flag to include examples in the options.
   *
   * @return array
   *   Keyed options that are supported.
   */
  public function getLastDelimitorTypes($include_examples = TRUE);

  /**
   * Defines the supported final delimitor behavior options.
   *
   * @param bool $include_examples
   *   Flag to include examples in the options.
   *
   * @return array
   *   Keyed options that are supported.
   */
  public function getLastDelimitorBehaviors($include_examples = TRUE);

}
