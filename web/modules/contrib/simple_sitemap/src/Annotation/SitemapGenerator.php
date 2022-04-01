<?php

namespace Drupal\simple_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SitemapGenerator item annotation object.
 *
 * @package Drupal\simple_sitemap\Annotation
 *
 * @see \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class SitemapGenerator extends Plugin {

  /**
   * The generator ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the generator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the generator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Default generator settings.
   *
   * @var array
   */
  public $settings = [];
}
