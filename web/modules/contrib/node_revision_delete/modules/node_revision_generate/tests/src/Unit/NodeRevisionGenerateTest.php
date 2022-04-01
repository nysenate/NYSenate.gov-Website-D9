<?php

namespace Drupal\Tests\node_revision_generate\Unit;

use Drupal\node_revision_generate\NodeRevisionGenerateBatch;
use Drupal\Tests\UnitTestCase;
use Drupal\node_revision_generate\NodeRevisionGenerate;
use Drupal\Tests\node_revision_generate\Traits\NodeRevisionGenerateTestTrait;

/**
 * Tests the NodeRevisionGenerate class methods.
 *
 * @group node_revision_generate
 * @coversDefaultClass \Drupal\node_revision_generate\NodeRevisionGenerate
 */
class NodeRevisionGenerateTest extends UnitTestCase {

  use NodeRevisionGenerateTestTrait;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * A date time instance.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $time;

  /**
   * The NodeRevisionGenerate Object.
   *
   * @var \Drupal\node_revision_generate\NodeRevisionGenerate
   */
  protected $nodeRevisionGenerate;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Time mock.
    $this->time = $this->createMock('Drupal\Component\Datetime\TimeInterface');
    // Connection mock.
    $this->connection = $this->createMock('Drupal\Core\Database\Connection');

    // Creating the object.
    $this->nodeRevisionGenerate = new NodeRevisionGenerate(
      $this->time,
      $this->connection,
      $this->getStringTranslationStub()
    );
  }

  /**
   * Tests the getRevisionCreationBatch() method.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $nodes_for_revisions
   *   The nodes for revisions array.
   * @param int $revisions_number
   *   Number of revisions to generate.
   * @param int $revisions_age
   *   Interval in Unix timestamp format to add to the last revision date of the
   *   node.
   *
   * @covers ::getRevisionCreationBatch
   * @dataProvider providerGetRevisionCreationBatch
   */
  public function testGetRevisionCreationBatch(array $expected, array $nodes_for_revisions, $revisions_number, $revisions_age) {
    // Mocking getRequestTime method.
    $this->time->expects($this->any())
      ->method('getRequestTime')
      ->willReturn($this->getRequestTime());

    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionGenerate->getRevisionCreationBatch($nodes_for_revisions, $revisions_number, $revisions_age));
  }

  /**
   * Data provider for testGetRevisionCreationBatch().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from ::getRevisionCreationBatch().
   *   - 'nodes_for_revisions' - The nodes for revisions array.
   *   - 'revisions_number' -  Number of revisions to generate.
   *   - 'revisions_age' - Time to add to the last revision date of the node.
   *
   * @see testGetRevisionCreationBatch()
   */
  public function providerGetRevisionCreationBatch() {
    // Sets of revisions.
    $revisions = $this->getRevisions();
    // The number of revisions to generate.
    $number = [1, 5, 2, 7, 3];

    // Set for the revisions age option.
    $revisions_age = $this->getRevisionAge();

    // The batch.
    $batch_template = [
      'title' => $this->getStringTranslationStub()->translate('Generating revisions'),
      'init_message' => $this->getStringTranslationStub()->translate('Starting to create revisions.'),
      'progress_message' => $this->getStringTranslationStub()->translate('Processed @current out of @total (@percentage%). Estimated time: @estimate.'),
      'error_message' => $this->getStringTranslationStub()->translate('The revision creation process has encountered an error.'),
      'operations' => [],
      'finished' => [NodeRevisionGenerateBatch::class, 'finish'],
      'file' => NULL,
      'library' => [],
      'url_options' => [],
      'progressive' => TRUE,
    ];

    $expected = [];

    // Creating the expected arrays.
    foreach ($revisions as $set => $nodes_for_revisions) {
      $expected[$set] = $batch_template;

      // Building batch operations, one per revision.
      foreach ($nodes_for_revisions as $node) {
        $revision_timestamp = $node->revision_timestamp;
        // Initializing variables.
        $i = 0;
        $revision_timestamp += $revisions_age[$set];
        // Adding operations.
        while ($i < $number[$set] && $revision_timestamp <= $this->getRequestTime()) {
          // Adding the operation.
          $expected[$set]['operations'][] = [
            [NodeRevisionGenerateBatch::class, 'generateRevisions'],
            [$node->nid, $revision_timestamp],
          ];
          $revision_timestamp += $revisions_age[$set];
          $i++;
        }
      }
    }

    $tests = [];
    $tests['t1'] = [$expected[0], $revisions[0], $number[0], $revisions_age[0]];
    $tests['t2'] = [$expected[1], $revisions[1], $number[1], $revisions_age[1]];
    $tests['t3'] = [$expected[2], $revisions[2], $number[2], $revisions_age[2]];
    $tests['t4'] = [$expected[3], $revisions[3], $number[3], $revisions_age[3]];
    $tests['t5'] = [$expected[4], $revisions[4], $number[4], $revisions_age[4]];

    return $tests;
  }

  /**
   * Tests the getAvailableNodesForRevisions() method.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $bundles
   *   An array with the selected content types to generate node revisions.
   * @param int $revisions_age
   *   Interval in Unix timestamp format to add to the last revision date of the
   *   node.
   *
   * @covers ::getAvailableNodesForRevisions
   * @dataProvider providerGetAvailableNodesForRevisions
   */
  public function testGetAvailableNodesForRevisions(array $expected, array $bundles, $revisions_age) {
    // Mocking getRequestTime method.
    $this->time->expects($this->any())
      ->method('getRequestTime')
      ->willReturn($this->getRequestTime());

    // Variable with the placeholders arguments needed for the expression.
    $interval_time = [
      ':interval' => $revisions_age,
      ':current_time' => $this->getRequestTime(),
    ];

    // StatementInterface mock.
    $statement = $this->createMock('Drupal\Core\Database\StatementInterface');
    // StatementInterface::fetchAll mock.
    $statement->expects($this->any())
      ->method('fetchAll')
      ->willReturn($expected);

    // SelectInterface mock.
    $select = $this->createMock('Drupal\Core\Database\Query\SelectInterface');
    // SelectInterface::execute mock.
    $select->expects($this->any())
      ->method('execute')
      ->willReturn($statement);
    // SelectInterface::where mock.
    $select->expects($this->any())
      ->method('where')
      ->with('revision.revision_timestamp + :interval <= :current_time', $interval_time)
      ->willReturn($this->returnSelf());
    // SelectInterface::condition mock.
    $select->method('condition')
      ->withConsecutive(['node.type', $bundles, 'IN'], ['node.status', 1])
      ->willReturnOnConsecutiveCalls($this->returnSelf(), $this->returnSelf());
    // SelectInterface::isNotNull mock.
    $select->expects($this->any())
      ->method('isNotNull')
      ->with('node.title')
      ->willReturn($this->returnSelf());
    // SelectInterface::addField mock.
    $select->method('addField')
      ->withConsecutive(
        ['node', 'nid'],
        ['revision', 'revision_timestamp']
      )
      ->willReturnOnConsecutiveCalls($this->returnSelf(), $this->returnSelf());
    // SelectInterface::leftJoin mock.
    $select->expects($this->any())
      ->method('leftJoin')
      ->with('node_revision', 'revision', 'node.vid = revision.vid')
      ->willReturn($this->returnSelf());

    // Mocking select method.
    $this->connection->expects($this->any())
      ->method('select')
      ->with('node_field_data', 'node')
      ->willReturn($select);

    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionGenerate->getAvailableNodesForRevisions($bundles, $revisions_age));
  }

  /**
   * Data provider for testGetAvailableNodesForRevisions().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from ::getAvailableNodesForRevisions().
   *   - 'bundles' - The content types to generate node revisions.
   *   - 'revisions_age' - Time to add to the last revision date of the node.
   *
   * @see testGetAvailableNodesForRevisions()
   */
  public function providerGetAvailableNodesForRevisions() {
    // Set for the bundles.
    $bundles = [];
    $bundles[0] = [
      'page',
      'blog',
      'article',
    ];
    $bundles[1] = [
      'house',
      'car',
    ];
    $bundles[2] = [
      'person',
      'dog',
      'cat',
      'bird',
    ];

    // Set for the revisions age option.
    $revisions_age = $this->getRevisionAge();
    // Getting the expected revisions.
    $expected = $this->getRevisions();

    $tests = [];
    $tests['t1'] = [$expected[0], $bundles[0], $revisions_age[0]];
    $tests['t2'] = [$expected[1], $bundles[1], $revisions_age[1]];
    $tests['t3'] = [$expected[2], $bundles[2], $revisions_age[2]];
    $tests['t4'] = [$expected[3], $bundles[1], $revisions_age[3]];
    $tests['t5'] = [$expected[4], $bundles[0], $revisions_age[4]];

    return $tests;
  }

  /**
   * Returns the revisions array.
   *
   * @return array
   *   The revisions.
   */
  private function getRevisions() {
    $node1 = new \stdClass();
    $node1->nid = 1;
    $node1->revision_timestamp = 1570222375;

    $node2 = new \stdClass();
    $node2->nid = 2;
    $node2->revision_timestamp = 1569253391;

    $node3 = new \stdClass();
    $node3->nid = 3;
    $node3->revision_timestamp = 1550234556;

    return [
      [$node1],
      [$node1, $node2],
      [$node1, $node2, $node3],
      [$node2, $node3],
      [$node1, $node3],
    ];
  }

  /**
   * Returns the request time.
   *
   * @return int
   *   The request time.
   */
  private function getRequestTime() {
    return 1579631058;
  }

  /**
   * Tests the existsNodesContentType() method.
   *
   * @param bool $expected
   *   The expected result from calling the function.
   * @param string $content_type
   *   Content type machine name.
   *
   * @covers ::existsNodesContentType
   * @dataProvider providerExistsNodesContentType
   */
  public function testExistsNodesContentType($expected, $content_type) {
    // StatementInterface mock.
    $statement = $this->createMock('Drupal\Core\Database\StatementInterface');
    // StatementInterface::fetchAll mock.
    $statement->expects($this->any())
      ->method('fetchField')
      ->willReturn($expected);

    // SelectInterface mock.
    $select = $this->createMock('Drupal\Core\Database\Query\SelectInterface');
    // SelectInterface::execute mock.
    $select->expects($this->any())
      ->method('execute')
      ->willReturn($statement);

    // SelectInterface::countQuery mock.
    $select->expects($this->any())
      ->method('countQuery')
      ->willReturn($select);
    // SelectInterface::condition mock.
    $select->expects($this->any())
      ->method('condition')
      ->with('type', $content_type)
      ->willReturn($this->returnSelf());
    // SelectInterface::addField mock.
    $select->expects($this->any())
      ->method('addField')
      ->with('node', 'nid')
      ->willReturn($this->returnSelf());

    // Mocking select method.
    $this->connection->expects($this->any())
      ->method('select')
      ->with('node')
      ->willReturn($select);

    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionGenerate->existsNodesContentType($content_type));
  }

  /**
   * Data provider for testExistsNodesContentType().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from ::getAvailableNodesForRevisions().
   *   - 'content_type' - The content types to generate node revisions.
   *
   * @see testExistsNodesContentType()
   */
  public function providerExistsNodesContentType() {
    $tests = [];
    $tests['t1'] = [TRUE, 'article'];
    $tests['t2'] = [FALSE, 'page'];
    $tests['t3'] = [FALSE, 'book'];
    $tests['t4'] = [TRUE, 'house'];
    $tests['t5'] = [FALSE, 'animal'];

    return $tests;
  }

}
