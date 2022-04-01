<?php

namespace Drupal\Tests\taxonomy_access_fix\Traits;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides common methods for functional tests of Taxonomy Access Fix module.
 */
trait TaxonomyAccessFixTestTrait {

  /**
   * Passes if an element matching the specified CSS selector is found.
   *
   * An optional element index may be passed.
   *
   * @param string $selector
   *   CSS selector of element.
   * @param int $index
   *   Element position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertElementByCssSelector($selector, $index = 0, $message = '') {
    $elements = $this->cssSelect($selector);
    $message = $message ? $message : new FormattableMarkup('Element with CSS selector %selector found.', [
      '%selector' => $selector,
    ]);
    $this->assertTrue(isset($elements[$index]), $message);
  }

  /**
   * Passes if an element matching the specified CSS selector is not found.
   *
   * An optional element index may be passed.
   *
   * @param string $selector
   *   CSS selector of element.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertNoElementByCssSelector($selector, $message = '') {
    $elements = $this->cssSelect($selector);
    $message = $message ? $message : new FormattableMarkup('Element with CSS selector %selector not found.', [
      '%selector' => $selector,
    ]);
    $this->assertTrue(empty($elements), $message);
  }

  /**
   * Passes if a a link whose href attribute ends with a string is found.
   *
   * @todo Remove once https://www.drupal.org/node/2031223 has been fixed.
   *
   * An optional element index may be passed.
   *
   * @param string $href
   *   What the href attribute of the link should end with.
   * @param int $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertLinkByEndOfHref($href, $index = 0, $message = '') {
    // This is an XPath 1.0 implementation of the ends-with() function.
    $links = $this->xpath('//a[:href = substring(@href, string-length(@href) - ' . (strlen($href) + 1) . ')]', [
      ':href' => $href,
    ]);
    $message = $message ? $message : new FormattableMarkup('Link with href %href found.', [
      '%href' => $href,
    ]);
    $this->assertTrue(isset($links[$index]), $message);
  }

  /**
   * Passes if a a link whose href attribute ends with a string is not found.
   *
   * @todo Remove once https://www.drupal.org/node/2031223 has been fixed.
   *
   * An optional element index may be passed.
   *
   * @param string $href
   *   What the href attribute of the link should end with.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertNoLinkByEndOfHref($href, $message = '') {
    // This is an XPath 1.0 implementation of the ends-with() function.
    $links = $this->xpath('//a[:href = substring(@href, string-length(@href) - ' . (strlen($href) + 1) . ')]', [
      ':href' => $href,
    ]);
    $message = $message ? $message : new FormattableMarkup('No link with href %href found.', [
      '%href' => $href,
    ]);
    $this->assertTrue(empty($links), $message);
  }

  /**
   * Asserts that the page contains a sortable taxonomy term table.
   *
   * Checks, wether the page contains a sortable table by checking for the
   * presence of a select element to set a weight for a term.
   *
   * @param bool $assert_overview_page
   *   Whether to assert sortable table on overview page (Default). If FALSE,
   *   sortable table on index page will be asserted.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertSortableTable($assert_overview_page = TRUE) {
    if ($assert_overview_page) {
      $this->assertSession()->pageTextContains('Weight for added term');
    }
    else {
      foreach ($this->vocabularies as $vocabulary) {
        $this->assertSession()->pageTextContains('Weight for ' . $vocabulary->label());
      }
    }
    $select_class = $assert_overview_page ? 'term-weight' : 'weight';
    $this->assertElementByCssSelector('select.' . $select_class);
  }

  /**
   * Asserts that the page contains no sortable taxonomy term table.
   *
   * Checks, wether the page contains a sortable table by checking for the
   * presence of a select element to set a weight for a term.
   *
   * @param bool $assert_overview_page
   *   Whether to assert no sortable table on overview page (Default). If FALSE,
   *   no sortable table on index page will be asserted.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   When the assertion failed.
   */
  protected function assertNoSortableTable($assert_overview_page = TRUE) {
    if ($assert_overview_page) {
      $this->assertSession()->pageTextNotContains('Weight for added term');
    }
    else {
      foreach ($this->vocabularies as $vocabulary) {
        $this->assertSession()->pageTextNotContains('Weight for ' . $vocabulary->label());
      }
    }
    $select_class = $assert_overview_page ? 'term-weight' : 'weight';
    $this->assertNoElementByCssSelector('select.' . $select_class);
  }

  /**
   * Installs modules and rebuilds all data structures.
   *
   * @param array $modules
   *   Modules to install.
   */
  protected function installModules(array $modules) {
    $module_installer = $this->container->get('module_installer');
    $is_module_installed = $module_installer->install($modules, TRUE);
    $this->assertTrue($is_module_installed, new FormattableMarkup('Enabled modules: %modules', [
      '%modules' => 'taxonomy_access_fix',
    ]));
    $this->rebuildAll();
  }

}
