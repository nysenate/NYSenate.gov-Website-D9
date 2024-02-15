<?php

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests ImageMagick subform and settings.
 *
 * @group imagemagick
 */
class ToolkitImagemagickFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'imagemagick', 'file_mdm'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->moduleList = \Drupal::service('extension.list.module');

    // Create an admin user.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test ImageMagick subform and settings.
   */
  public function testFormAndSettings(): void {
    $admin_path = 'admin/config/media/image-toolkit';

    // Change the toolkit.
    \Drupal::configFactory()->getEditable('system.image')
      ->set('toolkit', 'imagemagick')
      ->save();

    // Test form is accepting wrong binaries path while setting toolkit to GD.
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals('image_toolkit', 'imagemagick');
    $edit = [
      'image_toolkit' => 'gd',
      'imagemagick[suite][path_to_binaries]' => '/foo/bar/',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('image_toolkit', 'gd');

    // Change the toolkit via form.
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals('image_toolkit', 'gd');
    $edit = [
      'image_toolkit' => 'imagemagick',
      'imagemagick[suite][path_to_binaries]' => '',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('image_toolkit', 'imagemagick');

    // Test default supported image extensions.
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('GIF, JPEG, PNG');
    $this->assertSession()->responseContains('gif, jpe, jpeg, jpg, png');

    $config = \Drupal::configFactory()->getEditable('imagemagick.settings');

    // Enable TIFF.
    $image_formats = $config->get('image_formats');
    $image_formats['TIFF']['enabled'] = TRUE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('GIF, JPEG, PNG, TIFF');
    $this->assertSession()->responseContains('gif, jpe, jpeg, jpg, png, tif, tiff');

    // Enable BMP.
    $image_formats['BMP']['enabled'] = TRUE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, PNG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jpe, jpeg, jpg, png, tif, tiff');

    // Disable PNG.
    $image_formats['PNG']['enabled'] = FALSE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jpe, jpeg, jpg, tif, tiff');

    // Disable some extensions.
    $image_formats['TIFF']['exclude_extensions'] = 'tif, gif';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jpe, jpeg, jpg, tiff');
    $image_formats['JPEG']['exclude_extensions'] = 'jpe, jpg';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jpeg, tiff');

    // Add a format with missing mimetype.
    $image_formats['BAX']['mime_type'] = 'foo/bar';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('Image format errors');
  }

}
