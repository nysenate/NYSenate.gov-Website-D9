<?php

namespace Drupal\Tests\prepopulate\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Field functionality tests of prepopulate.
 *
 * @requires module inline_entity_form
 *
 * @group prepopulate
 */
class PrepopulateFieldTest extends BrowserTestBase {

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
    'filter',
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
   * A stub term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer taxonomy',
    ]);
    $this->drupalLogin($this->user);
    $this->term = Term::create([
      'vid' => 'tags',
      'name' => $this->randomMachineName(),
    ]);
    $this->term->save();
  }

  /**
   * Test pre-populating fields.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testFieldsPrepopulate() {
    foreach ($this->allFields() as $input) {
      $this->assertPrepopulate($input['query'], $input['expected']);
    }
  }

  /**
   * Assert all values are prepopulated as expected.
   *
   * @param array $query
   *   The prepopulated query strings.
   * @param string $expected
   *   The expected results populated in the node.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertPrepopulate(array $query, $expected) {
    // Title is required.
    $query[] = 'edit[title][widget][0][value]=simple title';
    // IEF taxonomy 'name' field is required.
    $query[] = 'edit[field_ief][widget][0][inline_entity_form][name][widget][0][value]=Apples';
    $this->drupalGet(Url::fromUri('internal:/node/add/test_content?' . implode('&', $query)));
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertSession()->pageTextContains('Test Content simple title has been created.');
    $this->assertSession()->pageTextContains($expected);
    $this->assertSession()->pageTextContains('IEF Apples');
  }

  /**
   * Test values of prepopulate with all field types.
   *
   * @return array
   *   The test data.
   */
  public function allFields() {
    $data['non existent reference'] = [
      'query' => [
        'edit[body][widget][0]=body text',
      ],
      'expected' => '',
    ];
    $data['body'] = [
      'query' => [
        'edit[body][widget][0][value]=body text',
        'edit[body][widget][0][summary]=body summary',
      ],
      'expected' => 'body text',
    ];
    $data['field_boolean'] = [
      'query' => [
        'edit[field_boolean][widget][value]=true',
      ],
      'expected' => 'Boolean On',
    ];
    $data['field_checkboxes'] = [
      'query' => [
        'edit[field_checkboxes][widget][Green]=true',
      ],
      'expected' => 'Checkboxes Green',
    ];
    $data['field_date'] = [
      'query' => [
        'edit[field_date][widget][0][value][date]=1970-01-01',
      ],
      'expected' => 'Date Thu, 01/01/1970 - 12:00',
    ];
    $data['field_date_range'] = [
      'query' => [
        'edit[field_date_range][widget][0][value][date]=1970-01-01',
        'edit[field_date_range][widget][0][value][time]=16:30:00',
        'edit[field_date_range][widget][0][end_value][date]=1970-01-02',
        'edit[field_date_range][widget][0][end_value][time]=13:30:00',
      ],
      'expected' => 'Date Range Thu, 01/01/1970 - 16:30 - Fri, 01/02/1970 - 13:30',
    ];
    $data['field_email'] = [
      'query' => [
        'edit[field_email][widget][0][value]=example@example.com',
      ],
      'expected' => 'Email example@example.com',
    ];
    $data['field_link'] = [
      'query' => [
        'edit[field_link][widget][0][uri]=https://example.com',
        'edit[field_link][widget][0][title]=Link Title',
      ],
      'expected' => 'Link Link Title https://example.com',
    ];
    $data['field_select_list'] = [
      'query' => [
        'edit[field_select_list][widget]=Green',
      ],
      'expected' => 'Select List Green',
    ];
    $data['field_select_list_numeric'] = [
      'query' => [
        'edit[field_select_list_numeric][widget]=0',
      ],
      'expected' => 'Select List Numeric Zero',
    ];
    $data['field_tags'] = [
      'query' => [
        'edit[field_tags][widget][target_id]=' . $this->term->id(),
      ],
      'expected' => 'Tags ' . $this->term->label(),
    ];
    $data['field_telephone'] = [
      'query' => [
        'edit[field_telephone][widget][0][value]=555-555-5555',
      ],
      'expected' => 'Telephone 555-555-5555',
    ];
    $data['field_time'] = [
      'query' => [
        'edit[field_time][widget][0][value][date]=1970-01-01',
        'edit[field_time][widget][0][value][time]=16:30:00',
      ],
      'expected' => 'Time Thu, 01/01/1970 - 16:30',
    ];
    $data['field_text'] = [
      'query' => [
        'edit[field_text][widget][0][value]=text string',
      ],
      'expected' => 'Text text string',
    ];
    $data['field_text_formatted'] = [
      'query' => [
        'edit[field_text_formatted][widget][0][value]=formatted text',
        'edit[field_text_formatted][widget][0][summary]=formatted text summary',
      ],
      'expected' => 'Text Formatted formatted text summary',
    ];
    return $data;
  }

}
