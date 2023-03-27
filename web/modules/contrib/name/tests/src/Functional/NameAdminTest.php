<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\name\Entity\NameFormat;
use Drupal\name\NameFormatInterface;

/**
 * Tests for the admin settings and custom format page.
 *
 * @group name
 */
class NameAdminTest extends NameTestBase {

  /**
   * Misc tests related to adding, updating and removing formats.
   */
  public function testAdminFormatSettings() {
    // Default settings and system settings.
    $this->drupalLogin($this->adminUser);

    // The default installed formats.
    $this->drupalGet('admin/config/regional/name');

    $row_template = [
      'title'       => '//tbody/tr[{row}]/td[1]',
      'machine'     => '//tbody/tr[{row}]/td[2]',
      'pattern'     => '//tbody/tr[{row}]/td[3]',
      'formatted'   => '//tbody/tr[{row}]/td[4]',
      'edit'        => '//tbody/tr[{row}]/td[5]//li/a[text()="Edit"]',
      'edit link'   => '//tbody/tr[{row}]/td[5]//li/a[text()="Edit"]/@href',
      'delete'      => '//tbody/tr[{row}]/td[5]//li/a[text()="Delete"]',
      'delete link' => '//tbody/tr[{row}]/td[5]//li/a[text()="Delete"]/@href',
    ];
    $all_values = [
      1 => [
        'title' => t('Default'),
        'machine' => 'default',
        'pattern' => '((((t+ig)+im)+if)+is)+jc',
        'formatted' => '(1) Mr John Peter Mark Doe Jnr., B.Sc., Ph.D. (2) JOAN SUE SMITH (3) Prince',
      ],
      2 => [
        'title' => t('Family'),
        'machine' => 'family',
        'pattern' => 'f',
        'formatted' => '(1) Doe (2) SMITH (3) &lt;&lt;empty&gt;&gt;',
        'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'family'])->toString(),
        'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'family'])->toString(),
      ],
      3 => [
        'title' => t('Full'),
        'machine' => 'full',
        'pattern' => '((((t+ig)+im)+if)+is)+jc',
        'formatted' => '(1) Mr John Peter Mark Doe Jnr., B.Sc., Ph.D. (2) JOAN SUE SMITH (3) Prince',
        'edit' => t('Edit'),
        'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'full'])->toString(),
        'delete' => t('Delete'),
        'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'full'])->toString(),
      ],
      4 => [
        'title' => t('Given'),
        'machine' => 'given',
        'pattern' => 'g',
        'formatted' => '(1) John (2) JOAN (3) Prince',
        'edit' => t('Edit'),
        'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'given'])->toString(),
        'delete' => t('Delete'),
        'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'given'])->toString(),
      ],
      5 => [
        'title' => t('Given Family'),
        'machine' => 'short_full',
        'pattern' => 'g+if',
        'formatted' => '(1) John Doe (2) JOAN SMITH (3) Prince',
        'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'short_full'])->toString(),
        'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'short_full'])->toString(),
      ],
      6 => [
        'title' => t('Title Family'),
        'machine' => 'formal',
        'pattern' => 't+if',
        'formatted' => '(1) Mr Doe (2) SMITH (3) &lt;&lt;empty&gt;&gt;',
        'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'formal'])->toString(),
        'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'formal'])->toString(),
      ],
    ];

    foreach ($all_values as $id => $row) {
      $this->assertRow($row, $row_template, $id);
    }

    // Load the name settings form.
    $this->drupalGet('admin/config/regional/name/settings');

    $default_values = [
      'name_settings[sep1]' => ' ',
      'name_settings[sep2]' => ', ',
      'name_settings[sep3]' => '',
    ];
    foreach ($default_values as $name => $value) {
      $this->assertSession()->fieldValueEquals($name, $value);
    }
    // ID example.
    $this->assertSession()->fieldValueEquals('edit-name-settings-sep1', ' ');

    $test_values = [
      'name_settings[sep1]' => '~',
      'name_settings[sep2]' => '^',
      'name_settings[sep3]' => '-',
    ];
    $this->drupalGet('admin/config/regional/name/settings');
    $this->submitForm($test_values, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));

    foreach ($test_values as $name => $value) {
      $this->assertSession()->fieldValueEquals($name, $value);
    }

    // Delete all existing formats.
    $formats = NameFormat::loadMultiple();
    array_walk($formats, function (NameFormatInterface $format) {
      if (!$format->isLocked()) {
        $format->delete();
      }
    });

    $this->drupalGet('admin/config/regional/name/add');
    $this->assertSession()->responseContains('Format string help');
    $values = ['label' => '', 'id' => '', 'pattern' => ''];
    $this->drupalGet('admin/config/regional/name/add');
    $this->submitForm($values, t('Save format'));
    foreach ([t('Name'), t('Machine-readable name'), t('Format')] as $title) {
      $this->assertSession()->pageTextContains(t('@field field is required', ['@field' => $title]));
    }
    $values = [
      'label' => 'given',
      'id' => '1234567890abcdefghijklmnopqrstuvwxyz_',
      'pattern' => 'a',
    ];
    $this->drupalGet('admin/config/regional/name/add');
    $this->submitForm($values, t('Save format'));
    $this->assertSession()->pageTextNotContains(t('@field field is required', ['@field' => t('Format')]));
    $this->assertSession()->pageTextNotContains(t('@field field is required', ['@field' => t('Machine-readable name')]));

    $values = ['label' => 'given', 'id' => '%&*(', 'pattern' => 'a'];
    $this->drupalGet('admin/config/regional/name/add');
    $this->submitForm($values, t('Save format'));
    $this->assertSession()->pageTextContains(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $values = ['label' => 'default', 'id' => 'default', 'pattern' => 'a'];
    $this->drupalGet('admin/config/regional/name/add');
    $this->submitForm($values, t('Save format'));
    $this->assertSession()->pageTextContains(t('The machine-readable name is already in use. It must be unique.'));

    $values = ['label' => 'Test', 'id' => 'test', 'pattern' => '\a\bc'];
    $this->drupalGet('admin/config/regional/name/add');
    $this->submitForm($values, t('Save format'));
    $this->assertSession()->pageTextContains(t('Name format Test added.'));

    $row = [
      'title' => 'Test',
      'machine' => 'test',
      'pattern' => '\a\bc',
      'formatted' => '(1) abB.Sc., Ph.D. (2) ab (3) ab',
      'edit link' => Url::fromRoute('entity.name_format.edit_form', ['name_format' => 'test'])->toString(),
      'delete link' => Url::fromRoute('entity.name_format.delete_form', ['name_format' => 'test'])->toString(),
    ];
    $this->assertRow($row, $row_template, 3);

    $values = ['label' => 'new name', 'pattern' => 'f+g'];
    $this->drupalGet('admin/config/regional/name/manage/test');
    $this->submitForm($values, t('Save format'));
    $this->assertSession()->pageTextContains(t('Name format new name has been updated.'));

    $row = [
      'label' => $values['label'],
      'id' => 'test',
      'pattern' => $values['pattern'],
    ];
    $this->assertRow($row, $row_template, 3);

    $this->drupalGet('admin/config/regional/name/manage/60');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/config/regional/name/manage/60/delete');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/config/regional/name/manage/test/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the custom format @title?', ['@title' => $values['label']]));

    $this->submitForm([], t('Delete'));
    $this->assertSession()->pageTextContains(t('The name format @title has been deleted.', ['@title' => $values['label']]));
  }

  /**
   * Misc tests related to adding, updating and removing formats.
   */
  public function testAdminListFormatSettings() {
    // Default settings and system settings.
    $this->drupalLogin($this->adminUser);

    // The default installed formats.
    $this->drupalGet('admin/config/regional/name/list');

    $row_template = [
      'title'       => '//tbody/tr[{row}]/td[1]',
      'machine'     => '//tbody/tr[{row}]/td[2]',
      'settings'    => '//tbody/tr[{row}]/td[3]',
      // 'examples'   => '//tbody/tr[{row}]/td[4]',
      'edit'        => '//tbody/tr[{row}]/td[5]//li/a[text()="Edit"]',
      'edit link'   => '//tbody/tr[{row}]/td[5]//li/a[text()="Edit"]/@href',
      'delete'      => '//tbody/tr[{row}]/td[5]//li/a[text()="Delete"]',
      'delete link' => '//tbody/tr[{row}]/td[5]//li/a[text()="Delete"]/@href',
    ];
    $all_values = [
      1 => [
        'title' => t('Default'),
        'machine' => 'default',
        // 'examples' => 'todo',
        'edit link' => Url::fromRoute('entity.name_list_format.edit_form', ['name_list_format' => 'default'])->toString(),
      ],
    ];

    foreach ($all_values as $id => $row) {
      $this->assertRow($row, $row_template, $id);
    }

    $this->drupalGet('admin/config/regional/name/list/add');

    // All bar delimiter are required.
    $values = [
      'label' => '',
      'id' => '',
      'delimiter' => '',
      // One or text or symbol.
      // 'and' => '',
      // One or never, always or contextual.
      // 'delimiter_precedes_last' => '',
      // Integers 0 (never reduce) through 20.
      // 'el_al_min' => '',
      // Integers 1 through 20.
      // 'el_al_first' => '',
    ];
    $this->drupalGet('admin/config/regional/name/list/add');
    $this->submitForm($values, t('Save list format'));
    $labels = [
      t('Name'),
      t('Machine-readable name'),
    ];
    foreach ($labels as $title) {
      $this->assertSession()->pageTextContains(t('@field field is required', ['@field' => $title]));
    }
    $values = [
      'label' => 'comma',
      'id' => '1234567890abcdefghijklmnopqrstuvwxyz_',
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => '14',
      'el_al_first' => '5',
    ];
    $this->drupalGet('admin/config/regional/name/list/add');
    $this->submitForm($values, t('Save list format'));
    $this->assertSession()->pageTextNotContains(t('@field field is required', ['@field' => t('Last delimiter type')]));
    $this->assertSession()->pageTextNotContains(t('@field field is required', ['@field' => t('Machine-readable name')]));

    $values['id'] = '%&*(';
    $this->drupalGet('admin/config/regional/name/list/add');
    $this->submitForm($values, t('Save list format'));
    $this->assertSession()->pageTextContains(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $values = ['label' => 'default', 'id' => 'default', 'delimiter' => 'a'];
    $this->drupalGet('admin/config/regional/name/list/add');
    $this->submitForm($values, t('Save list format'));
    $this->assertSession()->pageTextContains(t('The machine-readable name is already in use. It must be unique.'));

    $values = [
      'label' => 'Test label',
      'id' => 'test',
      'delimiter' => ' / ',
      'and' => 'symbol',
      'delimiter_precedes_last' => 'contextual',
      'el_al_min' => '3',
      'el_al_first' => '1',
    ];
    $this->drupalGet('admin/config/regional/name/list/add');
    $this->submitForm($values, t('Save list format'));
    $this->assertSession()->pageTextContains(t('Name list format Test label added.'));

    $row = [
      'title' => 'Test label',
      'machine' => 'test',
      'delimiter' => ' / ',
      'edit link' => Url::fromRoute('entity.name_list_format.edit_form', ['name_list_format' => 'test'])->toString(),
      'delete link' => Url::fromRoute('entity.name_list_format.delete_form', ['name_list_format' => 'test'])->toString(),
    ];
    $this->assertRow($row, $row_template, 3);
    $summary_text = [
      'Delimiters: " / " and Ampersand (&amp;)',
      'Reduce after 3 items and show 1 items followed by el al.',
      'Last delimiter: Contextual',
    ];
    $this->assertRowContains(['settings' => $summary_text], $row_template, 3);

    $this->drupalGet('admin/config/regional/name/list/manage/60');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/config/regional/name/list/manage/60/delete');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/config/regional/name/list/manage/test/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the custom list format @title?', ['@title' => $values['label']]));

    $this->submitForm([], t('Delete'));
    $this->assertSession()->pageTextContains(t('The name list format @title has been deleted.', ['@title' => $values['label']]));
  }

  /**
   * Helper function to test a table cell via its expected value.
   *
   * @param array $row
   *   Table rows to test.
   * @param array $row_template
   *   The parameters used for each row test.
   * @param int $id
   *   The row ID.
   */
  public function assertRow(array $row, array $row_template, $id) {
    foreach ($row as $cell_code => $value) {
      if (isset($row_template[$cell_code])) {
        $xpath = str_replace('{row}', $id, $row_template[$cell_code]);
        $elements = $this->xpath($xpath);

        // Check URLs with or without the ?destination= query parameter.
        if (strpos($row_template[$cell_code], '/@href')) {
          $results = isset($elements[0]) ? $elements[0]->getParent()->getAttribute('href') : '';
          $message = "Testing {$cell_code} on row {$id} using '{$xpath}' and expecting '" . Html::escape($value) . "', got '" . Html::escape($results) . "'.";
          if ($results == $value || strpos($results, $value . '?destination=') === 0) {
          } else {
            $this->fail($message);
          }
        }
        else {
          $results = $this->normalizeOutput($elements);
          $message = "Testing {$cell_code} on row {$id} using '{$xpath}' and expecting '" . Html::escape($value) . "', got '" . Html::escape($results) . "'.";
          $this->assertEquals($results, $value, $message);
        }
      }
    }
  }

  /**
   * Helper function to test a table cell via its expected value.
   *
   * @param array $row
   *   Table rows to test.
   * @param array $row_template
   *   The parameters used for each row test.
   * @param int $id
   *   The row ID.
   */
  public function assertRowContains(array $row, array $row_template, $id) {
    foreach ($row as $cell_code => $values) {
      if (isset($row_template[$cell_code])) {
        $xpath = str_replace('{row}', $id, $row_template[$cell_code]);
        $raw_xpath = $this->xpath($xpath);
        $results = $this->normalizeOutput($raw_xpath);
        $values = (array) $values;
        foreach ($values as $value) {
          $message = "{$cell_code} [{$id}] '{$xpath}': testing '{$value}'; got '{$results}'.";
          $this->assertTrue((strpos($results, $value) !== FALSE), $message);
        }
      }
    }
  }

  /**
   * Helper function to normalize output for testing results.
   *
   * Normalizes text by:
   * - gets complete HTML structure of the child nodes.
   * - ensures whitespace around any HTML tags.
   * - removes newlines and ensures single whitespaces.
   * - trims the string for trailing whitespace.
   *
   * @param array|false $elements
   *   Raw results from the XML Path lookup.
   *
   * @return string
   *   A normalized string.
   */
  protected function normalizeOutput($elements = []) {
    if (!is_array($elements)) {
      return '__MISSING__';
    }

    $results = '';
    foreach ($elements as $element) {
      $results .= $element->getHtml();
    }
    // Insert a single whitespace in front of all opening HTML tags.
    $results = preg_replace('/<(?!\/)/', ' <', $results);
    // Normalize any newlines.
    $results = str_replace(["\r", "\n"], ["\n", " "], $results);
    $results = strip_tags($results);
    // Normalize any remaining whitespaces into a single space.
    $results = preg_replace('/\s+/', ' ', $results);
    return trim($results);
  }

}
