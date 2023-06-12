<?php

/**
 * @file
 * Hooks provided by the Rabbit Hole module.
 */

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alter rabbit hole values before the Rabbit Hole plugin is loaded.
 *
 * @param array $values
 *   The current Rabbit Hole values array.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity to apply rabbit hole behavior on.
 */
function hook_rabbit_hole_values_alter(array &$values, \Drupal\Core\Entity\ContentEntityInterface $entity) {
  // Disable bypassing access for everyone (including administrators).
  $values['bypass_access'] = FALSE;

  // Change action for some special cases.
  if ($entity->isTranslatable() && $values['rh_action'] === 'access_denied') {
    $values['rh_action'] = 'display_page';
  }
}

/**
 * Alter the response after the plugin's performAction() is executed.
 *
 * @param \Symfony\Component\HttpFoundation\Response $response
 *   The current action response.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity to apply rabbit hole behavior on.
 */
function hook_rabbit_hole_response_alter(Response &$response, \Drupal\Core\Entity\ContentEntityInterface $entity) {
  if ($response instanceof RedirectResponse) {
    $response = new TrustedRedirectResponse('https://example.com');
  }
}
