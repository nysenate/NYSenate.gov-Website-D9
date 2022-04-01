<?php

namespace Drupal\simple_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SitemapType item annotation object.
 *
 * @package Drupal\simple_sitemap\Annotation
 *
 * @see \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class SitemapType extends Plugin {

  /**
   * The sitemap type ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the sitemap type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the sitemap type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The ID of the sitemap generator.
   *
   * @var string
   */
  public $sitemapGenerator;

  /**
   * The IDs of the URL generators.
   *
   * @var[] string
   */
  public $urlGenerators = [];
}
