<?php

namespace Drupal\Tests\media_migration\Functional;

/**
 * Base class for testing media token transformations.
 */
abstract class MigrateEmbedMediaTokenTestBase extends MigrateMediaTestBase {

  /**
   * The CSS selector of the embed media entity.
   *
   * @var string
   */
  protected $embedMediaCssSelector;

  /**
   * {@inheritdoc}
   */
  protected function getExpectedEntities() {
    $expected_entities = parent::getExpectedEntities();
    if (!array_key_exists('filtered_html', $expected_entities['filter_format'])) {
      $expected_entities['filter_format']['filtered_html'] = 'Filtered HTML';
    }
    return $expected_entities;
  }

  /**
   * Asserts the result of Media Migration's embed media token transform.
   *
   * @param string|bool[][] $embed_code_html_properties
   *   The expected attributes of the embed entity HTML tags, keyed by their
   *   delta (from their order in node with ID '1').
   *   If a property value is set to TRUE, than this method checks only its
   *   existence.
   *   Example with teo expected embed code:
   *   @code
   *   array(
   *     0 => array(
   *       'attribute-with-value' => 'value',
   *       'attribute-exists' => TRUE,
   *     ),
   *     1 => array(
   *       'attribute-with-value' => 'value',
   *       'attribute-exists' => TRUE,
   *     ),
   *   )
   *   @endcode
   */
  protected function assertMediaTokenTransform(array $embed_code_html_properties) {
    $assert_session = $this->assertSession();

    // Assert that media_filter plugin tokens were transformed to entity_embed
    // HTML entities, and the node can be viewed and edited without errors.
    $this->drupalGet('node/1');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Article with embed image media');

    // The embed media' should be rendered properly.
    $this->assertRenderedEmbedMedia();

    // Node can be edited.
    $this->drupalGet('node/1/edit');
    $assert_session->statusCodeEquals(200);

    // Node body text contains the expected HTML entity of the embed media.
    $body_textarea = $assert_session->fieldExists('body[0][value]');
    $body_text = preg_replace('/\s+/', ' ', $body_textarea->getValue());

    $this->assertEmbedTokenHtmlTags($body_text, $embed_code_html_properties);

    // Hitting save button should not cause errors.
    $this->submitForm([], 'Save');
    $assert_session->statusCodeEquals(200);

    // Url is node/1.
    $this->assertSame($this->buildUrl('node/1'), $this->getUrl());
    // Embed media is still rendered.
    $this->assertRenderedEmbedMedia();
  }

  /**
   * Ensures that the rendered embed media exists.
   */
  protected function assertRenderedEmbedMedia() {
    // The embed media's image field should be present.
    $this->assertSession()->responseNotContains('The referenced media source is missing and needs to be re-embedded.');
    $this->assertNotNull($this->getSession()->getPage()->find('css', $this->embedMediaCssSelector));
  }

}
