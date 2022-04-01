<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ProfileExtensionList class.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ProfileExtensionList
 *
 * @group Extension
 */
class ProfileExtensionListTest extends KernelTestBase {

  /**
   * Tests getting profile info.
   *
   * @covers ::getExtensionInfo
   */
  public function testGetExtensionInfo() {
    /** @var \Drupal\Core\Extension\ProfileExtensionList $profile_list */
    $profile_list = $this->container->get('extension.list.profile');

    $info = $profile_list->getExtensionInfo('testing_inherited');
    $this->assertNotEmpty($info);
    $this->assertSame($info['name'], 'Testing Inherited');
    $this->assertSame($info['base profile'], 'testing');
    $this->assertContains('config', $info['install']);
    $this->assertContains('drupal:page_cache', $info['install']);
    $this->assertTrue($info['hidden'], 'Profiles should be hidden');

    // Test that profiles without any base return normalized info.
    $info = $profile_list->getExtensionInfo('minimal');
    $this->assertSame('', $info['base profile']);

    // Tests three levels profile inheritance.
    $info = $profile_list->getExtensionInfo('testing_sub_sub_profile');
    $this->assertSame($info['base profile'], 'testing_inherited');
  }

  /**
   * Tests getting profile dependency list.
   *
   * @covers ::getAncestors
   */
  public function testGetAncestors() {
    /** @var \Drupal\Core\Extension\ProfileExtensionList $profile_list */
    $profile_list = $this->container->get('extension.list.profile');

    $profiles = $profile_list->getAncestors('testing');
    $this->assertCount(1, $profiles);

    $profiles = $profile_list->getAncestors('testing_inherited');
    $this->assertCount(2, $profiles);

    $profiles = $profile_list->getAncestors('testing_sub_sub_profile');
    $this->assertCount(3, $profiles);

    $first_profile = current($profiles);
    $this->assertInstanceOf(Extension::class, $first_profile);
    $this->assertSame($first_profile->getName(), 'testing');
    $this->assertSame(1000, $first_profile->weight);
    $this->assertObjectHasAttribute('origin', $first_profile);

    $second_profile = next($profiles);
    $this->assertInstanceOf(Extension::class, $second_profile);
    $this->assertSame($second_profile->getName(), 'testing_inherited');
    $this->assertSame(1001, $second_profile->weight);
    $this->assertObjectHasAttribute('origin', $second_profile);

    $third_profile = next($profiles);
    $this->assertInstanceOf(Extension::class, $third_profile);
    $this->assertSame($third_profile->getName(), 'testing_sub_sub_profile');
    $this->assertSame(1002, $third_profile->weight);
    $this->assertObjectHasAttribute('origin', $third_profile);
  }

  /**
   * @covers ::selectDistribution
   *
   * @depends testGetExtensionInfo
   */
  public function testSelectDistribution() {
    $profile_list = new TestProfileExtensionList(
      $this->container->get('app.root'),
      'profile',
      $this->container->get('cache.default'),
      $this->container->get('info_parser'),
      $this->container->get('module_handler'),
      $this->container->get('state'),
      $this->container->getParameter('install_profile')
    );

    $profiles = ['testing', 'testing_inherited'];
    $base_info = $profile_list->getExtensionInfo('minimal');
    $profile_info = $profile_list->getExtensionInfo('testing_inherited');

    // Neither profile has distribution set.
    $distribution = $profile_list->selectDistribution($profiles);
    $this->assertEmpty($distribution, 'No distribution should be selected');

    // Set base profile distribution.
    $base_info['distribution']['name'] = 'Minimal';
    $profile_list->profileInfo['minimal'] = $base_info;
    // Base profile distribution should not be selected.
    $distribution = $profile_list->selectDistribution($profiles);
    $this->assertEmpty($distribution, 'Base profile distribution should not be selected');

    // Set main profile distribution.
    $profile_info['distribution']['name'] = 'Testing Inherited';
    $profile_list->profileInfo['testing_inherited'] = $profile_info;
    // Main profile distribution should be selected.
    $distribution = $profile_list->selectDistribution($profiles);
    $this->assertEquals($distribution, 'testing_inherited');
  }

}

final class TestProfileExtensionList extends ProfileExtensionList {

  /**
   * Overridden profile info, keyed by extension name.
   *
   * @var array
   */
  public $profileInfo = [];

  /**
   * {@inheritdoc}
   */
  public function getList() {
    $extensions = parent::getList();

    foreach ($extensions as $name => $extension) {
      if (isset($this->profileInfo[$name])) {
        $extension->info = $this->profileInfo[$name];
      }
    }
    return $extensions;
  }

}
