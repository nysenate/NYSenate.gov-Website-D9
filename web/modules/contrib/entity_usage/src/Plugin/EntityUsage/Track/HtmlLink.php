<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Component\Utility\Html;

/**
 * Tracks usage of entities referenced from regular HTML Links.
 *
 * @EntityUsageTrack(
 *   id = "html_link",
 *   label = @Translation("HTML links"),
 *   description = @Translation("Tracks relationships created with standard links inside formatted text fields."),
 *   field_types = {"text", "text_long", "text_with_summary"},
 * )
 */
class HtmlLink extends TextFieldEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];

    // Loop trough all the <a> elements that don't have the LinkIt attributes.
    $xpath_query = "//a[@href != '']";
    foreach ($xpath->query($xpath_query) as $element) {
      /** @var \DOMElement $element */
      try {
        // Get the href value of the <a> element.
        $href = $element->getAttribute('href');
        $entity = $this->findEntityByUrlString($href);
        if ($entity) {
          if ($element->hasAttribute('data-entity-uuid')) {
            // Normally the Linkit plugin handles when a element has this
            // attribute, but sometimes users may change the HREF manually and
            // leave behind the wrong UUID.
            $data_uuid = $element->getAttribute('data-entity-uuid');
            // If the UUID is the same as found in HREF, then skip it because
            // it's LinkIt's job to register this usage.
            if ($data_uuid === $entity->uuid()) {
              continue;
            }
          }
          $entities[$entity->uuid()] = $entity->getEntityTypeId();
        }
      }
      catch (\Exception $e) {
        // Do nothing.
      }
    }

    return $entities;
  }

}
