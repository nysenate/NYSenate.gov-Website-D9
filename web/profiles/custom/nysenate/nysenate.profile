<?php

/**
 * @file
 * Enables modules and site configuration for the nysenate profile.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;
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

/**
 * Add links to Issue Explorer Menu.
 */
function nysenate_deploy_explore_issues_menu() {
  $menu_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $menu_name = 'menu-issue-explorer';

  $links = [
    [
      'title' => 'Alphabetical',
      'uri' => 'internal:/explore-issues',
      'weight' => 0,
    ],
    [
      'title' => 'Most Recent',
      'uri' => 'internal:/explore-issues/most-recent',
      'weight' => 1,
    ],
    [
      'title' => 'Popular w/ Senators',
      'uri' => 'internal:/explore-issues/most-popular',
      'weight' => 2,
    ],
    [
      'title' => 'Most Followed',
      'uri' => 'internal:/explore-issues/most-followed',
      'weight' => 3,
    ],
  ];

  foreach ($links as $link) {
    $menu_link = $menu_storage->loadByProperties([
      'title' => $link['title'],
      'menu_name' => $menu_name,
    ]);

    // Update menu-issue-explorer menu item link.
    if ($menu_link) {
      $menu_link = reset($menu_link);
      $menu_link->set('link', ['uri' => $link['uri']]);
      $menu_link->set('weight', $link['weight']);
      $menu_link->save();
    }

    // Create menu-issue-explorer menu item.
    else {
      $menu_link = MenuLinkContent::create([
        'title' => $link['title'],
        'menu_name' => $menu_name,
        'link' => ['uri' => $link['uri']],
        'weight' => $link['weight'],
      ]);
      $menu_link->save();
    }
  }
}
