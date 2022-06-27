<?php

namespace Drupal\simple_sitemap\Manager;

use Drupal\simple_sitemap\Entity\SimpleSitemap;

/**
 * Provides a helper to setting/getting variants.
 */
trait VariantSetterTrait {

  /**
   * The currently set variants.
   *
   * @var array
   */
  protected $variants;

  /**
   * Sets the variants.
   *
   * @param array|string|null $variants
   *   array: Array of variants to be set.
   *   string: A particular variant to be set.
   *   null: All existing variants will be set.
   *
   * @return $this
   *
   * @todo Check if variants exist and throw exception.
   */
  public function setVariants($variants = NULL): self {
    if ($variants === NULL) {
      // @todo No need to load all sitemaps here.
      $this->variants = array_keys(SimpleSitemap::loadMultiple());
    }
    else {
      $this->variants = (array) $variants;
    }

    return $this;
  }

  /**
   * Gets the currently set variants, or all variants if none are set.
   *
   * @return array
   *   The currently set variants, or all variants if none are set.
   */
  protected function getVariants(): array {
    if (NULL === $this->variants) {
      $this->setVariants();
    }

    return $this->variants;
  }

}
