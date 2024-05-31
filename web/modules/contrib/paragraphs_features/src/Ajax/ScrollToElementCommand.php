<?php

namespace Drupal\paragraphs_features\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for scrolling an element into the view.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.scrollToElement.
 */
class ScrollToElementCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $elementSelector;

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $parentSelector;

  /**
   * Constructs a ScrollToElementCommand object.
   *
   * @param string $elementSelector
   *   The data-drupal-selector for the paragraphs element.
   * @param string $parentSelector
   *   The data-drupal-selector for the paragraphs field.
   */
  public function __construct(string $elementSelector, string $parentSelector) {
    $this->elementSelector = $elementSelector;
    $this->parentSelector = $parentSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'scrollToElement',
      'drupalElementSelector' => $this->elementSelector,
      'drupalParentSelector' => $this->parentSelector,
    ];
  }

}
