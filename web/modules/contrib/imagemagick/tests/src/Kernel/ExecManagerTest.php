<?php

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for ImagemagickExecManager.
 *
 * @group imagemagick
 */
class ExecManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['imagemagick', 'file_mdm', 'sophron'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['imagemagick', 'file_mdm', 'sophron']);
  }

  /**
   * Test missing command on ExecManager.
   *
   * @group legacy
   */
  public function testExecManagerCommandNotFound(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $expected = substr(PHP_OS, 0, 3) !== 'WIN' ? 127 : 1;
    $ret = $execManager->runOsShell('pinkpanther', '-inspector Clouseau', 'blake', $output, $error);
    $this->assertEquals($expected, $ret, $error);
  }

  /**
   * Test timeout on ExecManager.
   *
   * @group legacy
   */
  public function testExecManagerTimeout(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $expected = substr(PHP_OS, 0, 3) !== 'WIN' ? [143, -1] : [1];
    // Set a short timeout (1 sec.) and run a process that is expected to last
    // longer (10 secs.). Should return a 'terminate' exit code.
    $execManager->setTimeout(1);
    $ret = $execManager->runOsShell('sleep', '10', 'sleep', $output, $error);
    $this->assertTrue(in_array($ret, $expected, TRUE), $error);
  }

  /**
   * Test missing command on ExecManager.
   */
  public function testProcessCommandNotFound(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $expected = substr(PHP_OS, 0, 3) !== 'WIN' ? 127 : 1;
    $ret = $execManager->runProcess(['pinkpanther', '-inspector', 'Clouseau'], 'blake', $output, $error);
    $this->assertEquals($expected, $ret, $error);
  }

  /**
   * Test timeout on ExecManager.
   */
  public function testProcessTimeout(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $expected = substr(PHP_OS, 0, 3) !== 'WIN' ? [143, -1] : [1];
    // Set a short timeout (1 sec.) and run a process that is expected to last
    // longer (10 secs.). Should return a 'terminate' exit code.
    $execManager->setTimeout(1);
    $ret = $execManager->runProcess(['sleep', '10'], 'sleep', $output, $error);
    $this->assertTrue(in_array($ret, $expected, TRUE), $error);
  }

  /**
   * Test deprecations.
   *
   * @group legacy
   */
  public function testDeprecations(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);

    $this->expectDeprecation('Drupal\\imagemagick\\ImagemagickExecManager::getPackage() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::getPackageSuite() instead. See https://www.drupal.org/node/3409315');
    $this->assertSame('imagemagick', $execManager->getPackage());
    $this->expectDeprecation('Drupal\\imagemagick\\ImagemagickExecManager::getPackage() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::getPackageSuite() instead. See https://www.drupal.org/node/3409315');
    $this->assertSame('bingo', $execManager->getPackage('bingo'));

    $this->expectDeprecation('Drupal\\imagemagick\\ImagemagickExecManager::getPackageLabel() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use PackageSuite::label() instead. See https://www.drupal.org/node/3409315');
    $this->assertSame('ImageMagick', (string) $execManager->getPackageLabel());
    $this->expectDeprecation('Drupal\\imagemagick\\ImagemagickExecManager::getPackageLabel() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use PackageSuite::label() instead. See https://www.drupal.org/node/3409315');
    $this->assertSame('bingo', $execManager->getPackageLabel('bingo'));
  }

}
