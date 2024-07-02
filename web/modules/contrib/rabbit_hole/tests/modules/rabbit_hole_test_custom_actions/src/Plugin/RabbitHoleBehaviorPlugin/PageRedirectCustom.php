<?php

namespace Drupal\rabbit_hole_test_custom_actions\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect;

/**
 * Redirects to another page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "page_redirect_custom",
 *   label = @Translation("Page redirect (custom)")
 * )
 */
class PageRedirectCustom extends PageRedirect {

}
