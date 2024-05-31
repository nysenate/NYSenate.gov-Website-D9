<?php

namespace Drupal\svg_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition);

    // Do not override the parent constructor to set extra class properties. The
    // constructor parameter order is different in different Drupal core
    // releases, even in minor releases in the same Drupal core version.
    $instance->fileUrlGenerator = $container->get('file_url_generator');

    return $instance;
  }

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

    /** @var \Drupal\image\ImageStyleInterface $imageStyle */
    $imageStyle = $this->imageStyleStorage->load($this->getSetting('image_style'));
    /** @var \Drupal\file\FileInterface[] $images */
    foreach ($images as $delta => $image) {
      $imageUri = $image->getFileUri();
      $isSvg = svg_image_is_file_svg($image);
      $url = ($imageStyle && !$isSvg)
        ? $imageStyle->buildUrl($imageUri)
        : $this->fileUrlGenerator->generateAbsoluteString($imageUri);

      $url = $this->fileUrlGenerator->transformRelative($url);

      // Add cacheability metadata from the image and image style.
      $cacheability = CacheableMetadata::createFromObject($image);
      if ($imageStyle) {
        $cacheability->addCacheableDependency(CacheableMetadata::createFromObject($imageStyle));
      }

      $elements[$delta] = ['#markup' => Markup::create($url)];
      $cacheability->applyTo($elements[$delta]);
    }

    return $elements;
  }

}
