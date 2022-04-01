<?php

namespace Drupal\Tests\google_tag\Functional;

/**
 * Tests the Google Tag Manager for a site with multiple containers.
 *
 * @group GoogleTag
 */
class GTMMultipleTest extends GTMTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createData() {
    parent::createData();

    $this->variables['default'] = (object) [
      'id' => 'default',
      'label' => 'Default',
      'weight' => 3,
      'container_id' => 'GTM-default',
      'include_environment' => '1',
      'environment_id' => 'env-7',
      'environment_token' => 'ddddddddddddddddddddd',
    ];

    $this->variables['primary'] = (object) [
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 2,
      'container_id' => 'GTM-primary',
      'include_environment' => '1',
      'environment_id' => 'env-1',
      'environment_token' => 'ppppppppppppppppppppp',
    ];

    $this->variables['secondary'] = (object) [
      'id' => 'secondary',
      'label' => 'Secondary',
      'weight' => 1,
      'container_id' => 'GTM-secondary',
      'include_environment' => '1',
      'environment_id' => 'env-2',
      'environment_token' => 'sssssssssssssssssssss',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function checkSnippetContents() {
    foreach ($this->variables as $key => $variables) {
      $message = "Start on container $key";
      parent::assertTrue(TRUE, $message);
      foreach ($this->types as $type) {
        $function = $type == 'noscript' ? 'getSnippetFromCache' : 'getSnippetFromFile';
        $contents = $this->$function($key, $type);
        $function = "verify{$type}Snippet";
        $this->$function($contents, $this->variables[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkPageResponse() {
    parent::checkPageResponse();

    $include_file = $this->config('google_tag.settings')->get('include_file');
    $include_file ? $this->checkPageResponseFile() : $this->checkPageResponseInline();
  }

  /**
   * Inspect the page response (based on file source).
   */
  protected function checkPageResponseFile() {
    foreach ($this->variables as $key => $variables) {
      $this->drupalGet('');
      $message = "Start on container $key";
      parent::assertTrue(TRUE, $message);
      foreach ($this->types as $type) {
        $uri = "$this->basePath/google_tag/{$key}/google_tag.$type.js";
        // Remove the if-else when core_version_requirement >= 9.3 for this module.
        if (\Drupal::hasService('file_url_generator')) {
          $generator = \Drupal::service('file_url_generator');
          $url = $generator->transformRelative($generator->generateAbsoluteString($uri));
        }
        else {
          $url = file_url_transform_relative(file_create_url($uri));
        }
        $function = "verify{$type}Tag";
        $this->$function($url, $this->variables[$key]);
      }
    }
  }

  /**
   * Inspect the page response (based on inline snippet).
   */
  protected function checkPageResponseInline() {
    foreach ($this->variables as $key => $variables) {
      $this->drupalGet('');
      $message = "Start on container $key";
      parent::assertTrue(TRUE, $message);
      foreach ($this->types as $type) {
        $contents = $this->getSnippetFromCache($key, $type);
        $function = "verify{$type}TagInline";
        $this->$function($this->variables[$key], $contents);
      }
    }
  }

}
