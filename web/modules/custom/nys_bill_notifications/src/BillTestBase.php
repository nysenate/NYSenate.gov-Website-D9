<?php

namespace Drupal\nys_bill_notifications;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for BillTest plugins.
 */
abstract class BillTestBase implements BillTestInterface, ContainerFactoryPluginInterface {

  /**
   * An optional priority.
   *
   * @var int
   */
  protected int $priority = 0;

  /**
   * The plugin's definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_definition);
  }

  /**
   * Constructor.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
    $this->priority = $this->getOriginalPriority();
  }

  /**
   * Recursive function to resolve a bill test against an update block.
   *
   * Executes a synchronized crawl between the OpenLeg update block and the
   * test pattern.  Each iteration returns TRUE if its pattern is matched.
   *
   * @param object $item
   *   A JSON-decoded bill update item from OpenLeg, or any sub-object.
   * @param array $test_array
   *   An individual test item.  Defaults to this test's $pattern.
   *
   * @return bool
   *   True if the item was matched by the test.
   */
  public static function resolveTest(object $item, array $test_array = []): bool {
    // Initialize test result.  This forces empty test arrays to fail.
    $final_test_result = ((bool) $item) && ((bool) $test_array);

    // Iterate the test array.
    foreach ($test_array as $key => $val) {
      // Every $key is a property which must exist, or the test fails.
      if (!property_exists($item, $key)) {
        $final_test_result = FALSE;
      }
      else {
        // Get this property's value from the update object.
        $test_item = $item->$key;
        // If the value is an array, recurse that level.  The test fails if
        // the $test_item is not also an object.
        if (is_array($val)) {
          $final_test_result = is_object($test_item) && static::resolveTest($test_item, $val);
        }
        // Anything other than boolean TRUE requires an explicit match against
        // the $test_item represented as a string.
        elseif ($val !== TRUE) {
          $final_test_result = static::wildcardMatch($val, (string) $test_item);
        }
      }

      // Short-circuit the tests if any of them fail.
      if (!$final_test_result) {
        break;
      }
    }
    return $final_test_result;
  }

  /**
   * Implements a replacement for fnmatch().
   *
   * The fnmatch() function is not POSIX-compliant, and sniffers complain about
   * calling it.  This method implements a similar search using preg_match().
   *
   * @param string $pattern
   *   An fnmatch()-style search pattern, e.g., 'd*.inc'.
   * @param string $haystack
   *   The text to search.
   *
   * @return bool
   *   True if haystack matches pattern.
   */
  protected static function wildcardMatch(string $pattern, string $haystack): bool {
    // Quote the patten, and change wildcards to regex style.
    $pattern = str_replace('\*', '.*', '/^' . preg_quote($pattern, '/') . '$/');
    return (bool) preg_match($pattern, $haystack);
  }

  /**
   * {@inheritDoc}
   */
  public function getPattern(): array {
    return $this->definition['pattern'];
  }

  /**
   * {@inheritDoc}
   */
  public function isMatch(object $update): bool {
    return static::resolveTest($update, $this->getPattern());
  }

  /**
   * Gets the original priority level from the definition.
   */
  public function getOriginalPriority(): int {
    return $this->definition['priority'] ?? 0;
  }

  /**
   * {@inheritDoc}
   */
  public function getPriority(): int {
    return $this->priority;
  }

  /**
   * {@inheritDoc}
   */
  public function setPriority(int $priority): self {
    $this->priority = $priority;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getId(): string {
    return $this->definition['id'];
  }

  /**
   * {@inheritDoc}
   */
  public function getName(): string {
    return $this->definition['name'];
  }

  /**
   * {@inheritDoc}
   */
  public function isDisabled(): bool {
    return (bool) ($this->definition['disabled'] ?? FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function isEnabled(): bool {
    return !$this->isDisabled();
  }

  /**
   * {@inheritDoc}
   *
   * Enforces the need to match this test.  If not, an empty array is returned.
   */
  public function context(object $update): array {
    return $this->isMatch($update) ? $this->doContext($update) : [];
  }

  /**
   * Actually generate the context array.
   */
  protected function doContext(object $update): array {
    return [];
  }

}
