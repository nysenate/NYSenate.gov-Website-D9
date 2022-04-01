<?php

namespace Drupal\Tests\charts\Unit\Services;

use Drupal\Tests\UnitTestCase;
use Drupal\charts\Services\ChartAttachmentService;

/**
 * @coversDefaultClass \Drupal\charts\Services\ChartAttachmentService
 * @group charts
 */
class ChartAttachmentServiceTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Services\ChartAttachmentService
   */
  private $chartAttachmentService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->chartAttachmentService = new ChartAttachmentService();
  }

  /**
   * Tests getter and setter for attachments.
   *
   * @param array $attachmentViews
   *   Array of attachments.
   *
   * @dataProvider attachmentViews
   */
  public function testAttachmentViews(array $attachmentViews) {
    $this->chartAttachmentService->setAttachmentViews($attachmentViews);
    $this->assertArrayEquals($attachmentViews, $this->chartAttachmentService->getAttachmentViews());
  }

  /**
   * Data provider for testAttachmentView().
   */
  public function attachmentViews() {
    yield [
      [
        new \stdClass(),
      ],
    ];
  }

}
