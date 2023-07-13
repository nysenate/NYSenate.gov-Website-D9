<?php

namespace Drupal\fontawesome_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\fontawesome\Plugin\Field\FieldType\FontAwesomeIcon;

/**
 * Media source wrapping around a Font Awesome icon field.
 *
 * @see \Drupal\fontawesome\Plugin\Field\FieldType\FontAwesomeIcon
 *
 * @MediaSource(
 *   id = "font_awesome_icon",
 *   label = @Translation("Font Awesome Icon"),
 *   description = @Translation("Use a Font Awesome Icon for reusable media."),
 *   allowed_field_types = {"fontawesome_icon"},
 *   default_thumbnail_filename = "generic.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\fontawesome_media\Form\FontawesomeMediaAddForm",
 *   }
 * )
 */
class FontAwesomeIconMedia extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'title' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    /** @var \Drupal\fontawesome\Plugin\Field\FieldType\FontAwesomeIcon $icon */
    $icon = $media
      ->get($this->configuration['source_field'])
      ->first();

    // If the source field is not required, it may be empty.
    if (!$icon) {
      return parent::getMetadata($media, $attribute_name);
    }
    switch ($attribute_name) {
      case 'default_name':
        return $icon
          ->get('icon_name')
          ->getValue();

      case 'thumbnail_uri':
        return $this->getThumbnail($icon);

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * Gets the thumbnail image URI based on an icon entity.
   *
   * @param \Drupal\fontawesome\Plugin\Field\FieldType\FontAwesomeIcon $icon
   *   A Font Awesome Icon entity.
   *
   * @return string
   *   File URI of the thumbnail image or NULL if there is no specific icon.
   */
  protected function getThumbnail(FontAwesomeIcon $icon) {

    // Determine the source folder.
    switch ($icon->get('style')->getValue()) {
      case 'fab':
        $srcFolder = 'brands';
        break;

      case 'fal':
        $srcFolder = 'light';
        break;

      case 'fas':
        $srcFolder = 'solid';
        break;

      case 'far':
      default:
        $srcFolder = 'regular';
        break;

      case 'fad':
        $srcFolder = 'duotone';
        break;

      case 'fat':
        $srcFolder = 'thin';
        break;

      case 'fak':
        $srcFolder = 'kit';
        break;
    }

    return 'libraries/fontawesome/svgs/' . $srcFolder . '/' . $icon
      ->get('icon_name')
      ->getValue() . '.svg';
  }

}
