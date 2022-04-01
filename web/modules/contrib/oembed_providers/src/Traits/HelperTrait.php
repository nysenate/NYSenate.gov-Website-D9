<?php

namespace Drupal\oembed_providers\Traits;

/**
 * Misc. methods available for all classes to use.
 */
trait HelperTrait {

  /**
   * Returns a security warning about disabled oEmbed providers.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   */
  public static function disabledProviderSecurityWarning() {
    return t("When a provider is disabled, Media will continue to render its content so long as the provider's definition is returned by Media's Provider Repository.");
  }

}
