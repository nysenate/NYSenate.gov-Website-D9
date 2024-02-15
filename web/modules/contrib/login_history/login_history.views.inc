<?php

/**
 * @file
 * Contains views configurations.
 */

/**
 * Implements hook_views_data().
 */
function login_history_views_data() {
  $data['login_history']['table']['group'] = t('Login History');
  // Allow users to create views showing user login history.
  $data['login_history']['table']['base'] = [
    'field' => 'uid',
    'title' => t('Login History'),
    'help' => t('User login history stored by the Login History module.'),
  ];

  // Describe join with users table.
  $data['login_history']['table']['join'] = [
    'users' => [
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];

  // Relationship with users table.
  $data['login_history']['uid'] = [
    'title' => t('User'),
    'help' => t('The user that logged in.'),
    'relationship' => [
      'id' => 'standard',
      'base' => 'users_field_data',
      'base field' => 'uid',
      'label' => t('User from Login History'),
    ],
  ];

  // Login timestamp field.
  $data['login_history']['login'] = [
    'title' => t('Date'),
    'help' => t('The date of the user login.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date_fulldate',
    ],
  ];

  // Hostname field.
  $data['login_history']['hostname'] = [
    'title' => t('IP Address'),
    'help' => t('IP address of the user login.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  // One time login field.
  $data['login_history']['one_time'] = [
    'title' => t('One-time login?'),
    'help' => t('Whether the login was through a one-time login link or not.'),
    'field' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
      'type' => 'yes-no',
      'label' => t('One-time login'),
      'use_equal' => TRUE,
    ],
  ];

  // User agent field.
  $data['login_history']['user_agent'] = [
    'title' => t('User Agent'),
    'help' => t('User agent of the user login.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function login_history_views_data_alter(array &$data) {
  $data['users']['login_history'] = [
    'title' => t('Logins'),
    'help' => t('Relate a user to historical logins'),
    'relationship' => [
      'id' => 'standard',
      'group' => 'Login History',
      'base' => 'login_history',
      'base field' => 'uid',
      'relationship field' => 'uid',
      'handler' => 'standard',
      'label' => t('Logins'),
      'field' => 'uid',
    ],
  ];
}
