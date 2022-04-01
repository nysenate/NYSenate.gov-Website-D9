<?php

namespace Drupal\svg_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;

/**
 * Plugin implementation of the 'image_url' formatter.
 *
 * Override default ImageUrlFormatter to proceed with svg urls.
 *
 * @FieldFormatter(
 *   id = "image_url",
 *   label = @Translation("URL to image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SvgImageUrlFormatter extends ImageUrlFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    if (empty($images = $this->getEntitiesToView($items, $langcode))) {
      // Early opt-out if the field is empty.
      return $elements;
    }

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = $this->imageStyleStorage->load($this->getSetting('image_style'));
    /** @var \Drupal\file\FileInterface[] $images */
    foreach ($images as $delta => $image) {
      $image_uri = $image->getFileUri();
      $isSvg = svg_image_is_file_svg($image);
      $url = ($image_style && !$isSvg) ? $image_style->buildUrl($image_uri) : file_create_url($image_uri);
      $url = file_url_transform_relative($url);

      // Add cacheability metadata from the image and image style.
      $cacheability = CacheableMetadata::createFromObject($image);
      if ($image_style) {
        $cacheability->addCacheableDependency(CacheableMetadata::createFromObject($image_style));
      }

      $elements[$delta] = ['#markup' => $url];
      $cacheability->applyTo($elements[$delta]);
    }
    return $elements;
  }

}
