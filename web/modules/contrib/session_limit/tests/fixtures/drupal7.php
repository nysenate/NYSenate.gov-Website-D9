<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('role_permission', array(
  'fields' => array(
    'rid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'permission' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'module' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
  ),
  'primary key' => array(
    'rid',
    'permission',
  ),
  'indexes' => array(
    'permission' => array(
      'permission',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('role_permission')
->fields(array(
  'rid',
  'permission',
  'module',
))
->values(array(
  'rid' => '1',
  'permission' => 'access comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '1',
  'permission' => 'access content',
  'module' => 'node',
))
->values(array(
  'rid' => '1',
  'permission' => 'use text format filtered_html',
  'module' => 'filter',
))
->values(array(
  'rid' => '2',
  'permission' => 'access comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '2',
  'permission' => 'access content',
  'module' => 'node',
))
->values(array(
  'rid' => '2',
  'permission' => 'post comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '2',
  'permission' => 'skip comment approval',
  'module' => 'comment',
))
->values(array(
  'rid' => '2',
  'permission' => 'use text format filtered_html',
  'module' => 'filter',
))
->values(array(
  'rid' => '3',
  'permission' => 'access administration pages',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'access all views',
  'module' => 'views',
))
->values(array(
  'rid' => '3',
  'permission' => 'access comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '3',
  'permission' => 'access content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'access content overview',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'access contextual links',
  'module' => 'contextual',
))
->values(array(
  'rid' => '3',
  'permission' => 'access dashboard',
  'module' => 'dashboard',
))
->values(array(
  'rid' => '3',
  'permission' => 'access library reports',
  'module' => 'libraries',
))
->values(array(
  'rid' => '3',
  'permission' => 'access overlay',
  'module' => 'overlay',
))
->values(array(
  'rid' => '3',
  'permission' => 'access site in maintenance mode',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'access site reports',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'access toolbar',
  'module' => 'toolbar',
))
->values(array(
  'rid' => '3',
  'permission' => 'access user profiles',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer actions',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer blocks',
  'module' => 'block',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer content types',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer fields',
  'module' => 'field',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer filters',
  'module' => 'filter',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer image styles',
  'module' => 'image',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer masquerade',
  'module' => 'masquerade',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer menu',
  'module' => 'menu',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer modules',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer nodequeue',
  'module' => 'nodequeue',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer nodes',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer permissions',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer search',
  'module' => 'search',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer session limits by role',
  'module' => 'session_limit',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer session limits per user',
  'module' => 'session_limit',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer shield',
  'module' => 'shield',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer shortcuts',
  'module' => 'shortcut',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer site configuration',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer software updates',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer taxonomy',
  'module' => 'taxonomy',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer themes',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer url aliases',
  'module' => 'path',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer users',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer video styles',
  'module' => 'video_embed_field',
))
->values(array(
  'rid' => '3',
  'permission' => 'administer views',
  'module' => 'views',
))
->values(array(
  'rid' => '3',
  'permission' => 'block IP addresses',
  'module' => 'system',
))
->values(array(
  'rid' => '3',
  'permission' => 'bypass node access',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'cancel account',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'change own username',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'create article content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'create page content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'create url aliases',
  'module' => 'path',
))
->values(array(
  'rid' => '3',
  'permission' => 'customize shortcut links',
  'module' => 'shortcut',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete any article content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete any page content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete own article content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete own page content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete revisions',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'delete terms in 1',
  'module' => 'taxonomy',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit any article content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit any page content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit own article content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit own comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit own page content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'edit terms in 1',
  'module' => 'taxonomy',
))
->values(array(
  'rid' => '3',
  'permission' => 'manipulate all queues',
  'module' => 'nodequeue',
))
->values(array(
  'rid' => '3',
  'permission' => 'manipulate queues',
  'module' => 'nodequeue',
))
->values(array(
  'rid' => '3',
  'permission' => 'masquerade as admin',
  'module' => 'masquerade',
))
->values(array(
  'rid' => '3',
  'permission' => 'masquerade as any user',
  'module' => 'masquerade',
))
->values(array(
  'rid' => '3',
  'permission' => 'masquerade as user',
  'module' => 'masquerade',
))
->values(array(
  'rid' => '3',
  'permission' => 'post comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '3',
  'permission' => 'revert revisions',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'search content',
  'module' => 'search',
))
->values(array(
  'rid' => '3',
  'permission' => 'select account cancellation method',
  'module' => 'user',
))
->values(array(
  'rid' => '3',
  'permission' => 'skip comment approval',
  'module' => 'comment',
))
->values(array(
  'rid' => '3',
  'permission' => 'switch shortcut sets',
  'module' => 'shortcut',
))
->values(array(
  'rid' => '3',
  'permission' => 'use advanced search',
  'module' => 'search',
))
->values(array(
  'rid' => '3',
  'permission' => 'use ctools import',
  'module' => 'ctools',
))
->values(array(
  'rid' => '3',
  'permission' => 'use text format filtered_html',
  'module' => 'filter',
))
->values(array(
  'rid' => '3',
  'permission' => 'use text format full_html',
  'module' => 'filter',
))
->values(array(
  'rid' => '3',
  'permission' => 'view own unpublished content',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'view revisions',
  'module' => 'node',
))
->values(array(
  'rid' => '3',
  'permission' => 'view the administration theme',
  'module' => 'system',
))
->values(array(
  'rid' => '4',
  'permission' => 'bypass node access',
  'module' => 'node',
))
->execute();

$connection->schema()->createTable('system', array(
  'fields' => array(
    'filename' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '12',
      'default' => '',
    ),
    'owner' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'bootstrap' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'schema_version' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'small',
      'default' => '-1',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'info' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'filename',
  ),
  'indexes' => array(
    'system_list' => array(
      'status',
      'bootstrap',
      'type',
      'weight',
      'name',
    ),
    'type_name' => array(
      'type',
      'name',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('system')
->fields(array(
  'filename',
  'name',
  'type',
  'owner',
  'status',
  'bootstrap',
  'schema_version',
  'weight',
  'info',
))
->values(array(
  'filename' => 'modules/user/user.module',
  'name' => 'user',
  'type' => 'module',
  'owner' => '',
  'status' => '1',
  'bootstrap' => '0',
  'schema_version' => '7019',
  'weight' => '0',
  'info' => 'a:15:{s:4:"name";s:4:"User";s:11:"description";s:47:"Manages the user registration and login system.";s:7:"package";s:4:"Core";s:7:"version";s:4:"7.82";s:4:"core";s:3:"7.x";s:5:"files";a:2:{i:0;s:11:"user.module";i:1;s:9:"user.test";}s:8:"required";b:1;s:9:"configure";s:19:"admin/config/people";s:11:"stylesheets";a:1:{s:3:"all";a:1:{s:8:"user.css";s:21:"modules/user/user.css";}}s:7:"project";s:6:"drupal";s:9:"datestamp";s:10:"1626883669";s:5:"mtime";i:1626883669;s:12:"dependencies";a:0:{}s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
))
->values(array(
  'filename' => 'sites/all/modules/contrib/session_limit/session_limit.module',
  'name' => 'session_limit',
  'type' => 'module',
  'owner' => '',
  'status' => '1',
  'bootstrap' => '0',
  'schema_version' => '0',
  'weight' => '0',
  'info' => 'a:13:{s:4:"name";s:13:"Session Limit";s:11:"description";s:33:"Limit simultaneous user sessions.";s:4:"core";s:3:"7.x";s:9:"configure";s:33:"admin/config/people/session-limit";s:5:"files";a:4:{i:0;s:21:"session_limit.install";i:1;s:20:"session_limit.module";i:2;s:24:"session_limit.tokens.inc";i:3;s:24:"tests/session_limit.test";}s:7:"version";s:7:"7.x-2.3";s:7:"project";s:13:"session_limit";s:9:"datestamp";s:10:"1540976892";s:5:"mtime";i:1540976892;s:12:"dependencies";a:0:{}s:7:"package";s:5:"Other";s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
))
->execute();

$connection->schema()->createTable('role', array(
  'fields' => array(
    'rid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => '',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
  ),
  'primary key' => array(
    'rid',
  ),
  'unique keys' => array(
    'name' => array(
      'name',
    ),
  ),
  'indexes' => array(
    'name_weight' => array(
      'name',
      'weight',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('role')
->fields(array(
  'rid',
  'name',
  'weight',
))
->values(array(
  'rid' => '3',
  'name' => 'administrator',
  'weight' => '2',
))
->values(array(
  'rid' => '1',
  'name' => 'anonymous user',
  'weight' => '0',
))
->values(array(
  'rid' => '2',
  'name' => 'authenticated user',
  'weight' => '1',
))
->values(array(
  'rid' => '4',
  'name' => 'Editor',
  'weight' => '3',
))
->execute();

$connection->schema()->createTable('variable', array(
  'fields' => array(
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'value' => array(
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ),
  ),
  'primary key' => array(
    'name',
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('variable')
->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'session_limit_behaviour',
  'value' => 's:1:"1";',
))
->values(array(
  'name' => 'session_limit_include_root_user',
  'value' => 'i:0;',
))
->values(array(
  'name' => 'session_limit_limit_hit_message',
  'value' => 's:372:"The maximum number of simultaneous sessions (@number) for your account has been reached. You did not log off from a previous session or someone else is logged on to your account. This may indicate that your account has been compromised or that account sharing is limited on this site. Please contact the site administrator if you suspect your account has been compromised.";',
))
->values(array(
  'name' => 'session_limit_logged_out_message',
  'value' => 's:366:"You have been automatically logged out. Someone else has logged in with your username and password and the maximum number of @number simultaneous sessions was exceeded. This may indicate that your account has been compromised or that account sharing is not allowed on this site. Please contact the site administrator if you suspect your account has been compromised.";',
))
->values(array(
  'name' => 'session_limit_logged_out_message_severity',
  'value' => 's:6:"status";',
))
->values(array(
  'name' => 'session_limit_masquerade_ignore',
  'value' => 'i:1;',
))
->values(array(
  'name' => 'session_limit_max',
  'value' => 's:1:"5";',
))
->values(array(
  'name' => 'session_limit_rid_2',
  'value' => 's:1:"1";',
))
->values(array(
  'name' => 'session_limit_rid_3',
  'value' => 's:1:"2";',
))
->values(array(
  'name' => 'session_limit_rid_4',
  'value' => 's:1:"3";',
))
->execute();
