<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Generates text using placeholders to check scheduler token replacement.
 *
 * @group scheduler
 */
class SchedulerTokenReplaceTest extends SchedulerBrowserTestBase {

  /**
   * Creates a node, then tests the tokens generated from it.
   *
   * @dataProvider dataSchedulerTokenReplacement()
   */
  public function testSchedulerTokenReplacement($entityTypeId, $bundle) {
    // For taxonomy, log in as adminUser to avoid 403 for unpublished terms.
    $entityTypeId == 'taxonomy_term' ? $this->drupalLogin($this->adminUser) : $this->drupalLogin($this->schedulerUser);
    // Define timestamps for consistent use when repeated throughout this test.
    $publish_on_timestamp = $this->requestTime + 3600;
    $unpublish_on_timestamp = $this->requestTime + 7200;

    // Create an unpublished entity with scheduled dates.
    $entity = $this->createEntity($entityTypeId, $bundle, [
      'status' => FALSE,
      'publish_on' => $publish_on_timestamp,
      'unpublish_on' => $unpublish_on_timestamp,
    ]);
    // Check that the entity is scheduled.
    $this->assertFalse($entity->isPublished(), 'The entity is not published');
    $this->assertNotEmpty($entity->publish_on->value, 'The entity has a publish_on date');
    $this->assertNotEmpty($entity->unpublish_on->value, 'The entity has an unpublish_on date');

    // Create array of test case data.
    $test_cases = [
      ['token_format' => '', 'date_format' => 'medium', 'custom' => ''],
      ['token_format' => ':long', 'date_format' => 'long', 'custom' => ''],
      ['token_format' => ':raw', 'date_format' => 'custom', 'custom' => 'U'],
      [
        'token_format' => ':custom:jS F g:ia e O',
        'date_format' => 'custom',
        'custom' => 'jS F g:ia e O',
      ],
    ];

    $storage = $this->entityStorageObject($entityTypeId);
    foreach ($test_cases as $test_data) {
      // Edit the entity and set the body tokens to use the format being tested.
      $edit = [
        "{$this->bodyField($entityTypeId)}[0][value]" => "Publish on: [{$entityTypeId}:scheduler-publish{$test_data['token_format']}]. Unpublish on: [{$entityTypeId}:scheduler-unpublish{$test_data['token_format']}].",
      ];
      $this->drupalGet($entity->toUrl('edit-form'));
      $this->submitForm($edit, 'Save');
      // View the entity.
      $this->drupalGet($entity->toUrl());

      // Refresh the entity and get the body output value using token replace.
      $storage->resetCache([$entity->id()]);
      $entity = $storage->load($entity->id());
      $body_output = \Drupal::token()->replace($entity->{$this->bodyField($entityTypeId)}->value, ["$entityTypeId" => $entity]);

      // Create the expected text for the body.
      $publish_on_date = $this->dateFormatter->format($publish_on_timestamp, $test_data['date_format'], $test_data['custom']);
      $unpublish_on_date = $this->dateFormatter->format($unpublish_on_timestamp, $test_data['date_format'], $test_data['custom']);
      $expected_output = 'Publish on: ' . $publish_on_date . '. Unpublish on: ' . $unpublish_on_date . '.';
      // Check that the actual text matches the expected value.
      $this->assertEquals($expected_output, $body_output, 'Scheduler tokens replaced correctly for ' . $test_data['token_format'] . ' format.');
    }
  }

  /**
   * Provides test data for TokenReplacement test.
   *
   * This test is not run for Media entities because there is no body field.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataSchedulerTokenReplacement() {
    $data = $this->dataStandardEntityTypes();
    unset($data['#media']);
    return $data;
  }

}
