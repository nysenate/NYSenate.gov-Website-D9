<?php

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node_revision_delete\Traits\NodeRevisionDeleteTestTrait;

/**
 * Test the module configurations related to the node_revision_delete service.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteConfigTest extends KernelTestBase {

  use NodeRevisionDeleteTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node_revision_delete',
    'node',
    'system',
    'field',
    'text',
  ];

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configurationFileName;

  /**
   * A test track array.
   *
   * @var array
   */
  protected $testTrackArray;

  /**
   * The node_revision_delete service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected $nodeRevisionDelete;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->configurationFileName = 'node_revision_delete.settings';
    // Installing the configuration file.
    $this->installConfig(self::$modules);

    // Getting the node revision delete service.
    $this->nodeRevisionDelete = $this->container->get('node_revision_delete');

    // Setting values for test.
    $this->testTrackArray = $this->getNodeRevisionDeleteTrackArray();
  }

  /**
   * Tests the NodeRevisionDelete::saveContentTypeConfig method.
   */
  public function testSaveContentTypeConfig() {
    foreach ($this->testTrackArray as $content_type => $content_type_info) {
      // Getting the config file.
      $config = $this->container->get('config.factory')->getEditable('node.type.' . $content_type);
      // Saving the array to have values in the config.
      $config->set('third_party_settings', $content_type_info)->save();

      $info = $content_type_info['node_revision_delete'];

      // Saving the configuration for a content type.
      $this->nodeRevisionDelete->saveContentTypeConfig($content_type, $info['minimum_revisions_to_keep'], $info['minimum_age_to_delete'], $info['when_to_delete']);

      // Asserting.
      $this->assertEquals($content_type_info, $config->get('third_party_settings'));
    }
  }

  /**
   * Tests the NodeRevisionDelete::deleteContentTypeConfig method.
   */
  public function testDeleteContentTypeConfig() {
    foreach ($this->testTrackArray as $content_type => $content_type_info) {
      // Getting the config file.
      $config = $this->container->get('config.factory')->getEditable('node.type.' . $content_type);
      // Saving the array to have values in the config.
      $config->set('third_party_settings', $content_type_info)->save();

      // Deleting the configuration for a content type.
      $this->nodeRevisionDelete->deleteContentTypeConfig($content_type);

      // Asserting.
      $this->assertEquals($this->testTrackArray[$content_type], $config->get('third_party_settings'));
    }
  }

}
