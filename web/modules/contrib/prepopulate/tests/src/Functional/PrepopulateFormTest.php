<?php

namespace Drupal\Tests\prepopulate\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Form functionality tests of prepopulate.
 *
 * @requires module inline_entity_form
 *
 * @group prepopulate
 */
class PrepopulateFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'inline_entity_form',
    'link',
    'node',
    'options',
    'path',
    'prepopulate',
    'prepopulate_test',
    'prepopulate_test_unsafe',
    'taxonomy',
    'telephone',
    'text',
    'user',
    'views',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A stub node page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->node = $this->drupalCreateNode([
      'title' => t('Hello, world!'),
      'type' => 'test_content',
      'uid' => $this->user->id(),
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test pre-populating all values into a form. Safe and unsafe inputs.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAllPrepopulate() {
    foreach ($this->allInputs() as $input) {
      $this->assertPrepopulate($input['uri'], $input['expected']);
    }
  }

  /**
   * Test pre-populating unsafe inputs in a form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUnsafePrepopulate() {
    $this->container->get('module_installer')->uninstall(['prepopulate_test_unsafe']);
    foreach ($this->unsafeInputs() as $input) {
      $this->assertPrepopulate($input['uri'], $input['expected']);
    }
  }

  /**
   * Test values of prepopulate with unsafe inputs.
   *
   * @return array
   *   The test data.
   */
  public function unsafeInputs() {
    $data['checkboxes'] = [
      'uri' => 'edit[checkboxes][green]=true',
      'expected' => 'checkboxes: array ( \'black\' => 0, \'blue\' => 0, \'green\' => 0, \'red\' => 0, \'white\' => 0, \'yellow\' => 0, )',
    ];
    $data['radios'] = [
      'uri' => 'edit[radios][north america]=north america',
      'expected' => 'radios: NULL',
    ];
    return $data;
  }

  /**
   * Assert all values are prepopulated as expected.
   *
   * @param string $query
   *   The prepopulated query string.
   * @param string $expected
   *   The expected result after submitting the form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertPrepopulate($query, $expected) {
    $this->drupalGet(Url::fromUri('internal:/prepopulate_test/form?' . $query));
    $this->drupalPostForm(NULL, [], 'Submit');
    $this->assertSession()->pageTextContains($expected);
  }

  /**
   * Test values of prepopulate with all inputs.
   *
   * @return array
   *   The test data.
   */
  public function allInputs() {
    $data['checkboxes'] = [
      'uri' => 'edit[checkboxes][green]=true',
      'expected' => 'checkboxes: array ( \'green\' => \'green\', \'black\' => 0, \'blue\' => 0, \'red\' => 0, \'white\' => 0, \'yellow\' => 0, )',
    ];
    $data['date'] = [
      'uri' => 'edit[date]=1970-01-01',
      'expected' => 'date: \'1970-01-01\'',
    ];
    $data['datelist'] = [
      'uri' => 'edit[datelist][year]=1970&edit[datelist][month]=1&edit[datelist][day]=1&edit[datelist][hour]=12&edit[datelist][minute]=59',
      'expected' => 'datelist: 1970-01-01 12:59:00',
    ];
    $data['datetime'] = [
      'uri' => 'edit[datetime][date]=1970-01-01&edit[datetime][time]=16:30:00',
      'expected' => 'datetime: 1970-01-01 16:30:00',
    ];
    $data['entity_autocomplete'] = [
      'uri' => 'edit[entity_autocomplete]=1',
      'expected' => 'entity_autocomplete: \'1\'',
    ];
    $data['entity_autocreate'] = [
      'uri' => 'edit[entity_autocreate]=Apples',
      'expected' => 'entity_autocreate: Apples (1)',
    ];
    $data['email'] = [
      'uri' => 'edit[email]=example%40example.com',
      'expected' => 'email: \'example@example.com\'',
    ];
    $data['machine_name'] = [
      'uri' => 'edit[machine_name]=non_existent_view',
      'expected' => 'machine_name: \'non_existent_view\'',
    ];
    $data['number'] = [
      'uri' => 'edit[number]=123',
      'expected' => 'number: \'123\'',
    ];
    $data['path'] = [
      'uri' => 'edit[path]=node/1',
      'expected' => 'path: array ( \'route_name\' => \'entity.node.canonical\', \'route_parameters\' => array ( \'node\' => \'1\', ), )',
    ];
    $data['radios'] = [
      'uri' => 'edit[radios][north america]=north america',
      'expected' => 'radios: \'north america\'',
    ];
    $data['telephone'] = [
      'uri' => 'edit[telephone]=555-555-5555',
      'expected' => 'telephone: \'555-555-5555\'',
    ];
    $data['textarea'] = [
      'uri' => 'edit[textarea]=hello world',
      'expected' => 'textarea: \'hello world\'',
    ];
    $data['textfield'] = [
      'uri' => 'edit[textfield]=foo bar',
      'expected' => 'textfield: \'foo bar\'',
    ];
    $data['select'] = [
      'uri' => 'edit[select]=east',
      'expected' => 'select: \'east\'',
    ];
    $data['url'] = [
      'uri' => 'edit[url]=http://www.example.com',
      'expected' => 'url: \'http://www.example.com\'',
    ];
    return $data;
  }

}
