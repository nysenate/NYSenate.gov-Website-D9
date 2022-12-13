<?php

/**
 * @file
 * Enables modules and site configuration for the nysenate profile.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\Component\Utility\Xss;

/**
 * Implements hook_form_alter().
 */
function nysenate_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Add captcha to all webforms that do not have one.
  if (array_key_exists('#webform_id', $form)) {
    if (array_key_exists('elements', $form)) {
      if (!array_key_exists('captcha', $form['elements'])) {
        $account = \Drupal::currentUser();
        $config = \Drupal::config('captcha.settings');
        if (!$account->hasPermission('skip CAPTCHA')) {
          $captchaService = \Drupal::service('captcha.helper');
          $captcha_point = new CaptchaPoint([
            'formId' => $form_id,
            'captchaType' => $config->get('default_challenge'),
          ], 'captcha_point');
          $captcha_point->enable();
          if ($captcha_point->status()) {
            // Checking if user's ip is whitelisted.
            if (captcha_whitelist_ip_whitelisted()) {
              // If form is setup to have captcha, but user's ip is whitelisted,
              // then we still have to disable form caching to prevent showing
              // cached form for users with not whitelisted ips.
              $form['#cache'] = ['max-age' => 0];
              \Drupal::service('page_cache_kill_switch')->trigger();
            }
            else {
              // Build CAPTCHA form element.
              $captcha_element = [
                '#type' => 'captcha',
                '#captcha_type' => $captcha_point->getCaptchaType(),
              ];

              // Add a CAPTCHA description if required.
              if ($config->get('add_captcha_description')) {
                $description = $config->get('description');
                $captcha_element['#description'] = Xss::filter($description);
              }

              // Place captcha in form.
              $captcha_placement = [
                'path' => [],
                'key' => 'actions',
                'weight' => 99,
              ];
              $captchaService->insertCaptchaElement($form, $captcha_placement, $captcha_element);
            }
          }
        }
      }
    }
  }
}
