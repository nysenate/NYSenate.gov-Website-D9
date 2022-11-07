<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\media\Entity\Media;

/**
 * Tests views contextual links on media items.
 *
 * @group media
 */
class MediaContextualLinksTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test is going to test the display, so we need the standalone URL.
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests contextual links.
   */
  public function testMediaContextualLinks() {
    // Create a media type.
    $mediaType = $this->createMediaType('test');

    // Create a media item.
    $media = Media::create([
      'bundle' => $mediaType->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $user = $this->drupalCreateUser([
      'administer media',
      'access contextual links',
      'view media',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('media/' . $media->id());

    // Contextual links are populated by javascript after the page is loaded.
    // Wait until they are on the page, click on the pencil so we make sure they
    // are visible, and then we can assert their contents.
    $this->assertSession()->waitForElement('css', 'div[data-contextual-id] ul.contextual-links');
    $this->getSession()->executeScript("jQuery('.contextual .trigger').toggleClass('visually-hidden');");
    $this->cssSelect('.contextual button')[0]->press();

    // The contextual links container is there.
    $this->assertSession()->elementAttributeContains('css', 'div[data-contextual-id]', 'data-contextual-id', 'media:media=' . $media->id() . ':');

    // The "Edit" link is there.
    $this->assertSession()->elementTextContains('css', 'ul.contextual-links li:first-child a', 'Edit');

    // The "Update metadata" link is there.
    $this->assertSession()->elementTextContains('css', 'ul.contextual-links li:nth-child(2) a', 'Update metadata');

    // The "Delete" link is there.
    $this->assertSession()->elementTextContains('css', 'ul.contextual-links li:nth-child(3) a', 'Delete');
  }

}
