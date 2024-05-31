<?php

namespace Drupal\password_policy\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Password policy migrate source plugin.
 *
 * @MigrateSource(
 *   id = "password_policy",
 *   source_module = "password_policy",
 * )
 */
class PasswordPolicy extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Unique name'),
      'config' => $this->t('Configuration'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('password_policy', 'password_policy')
      ->fields('password_policy');
  }

  /**
   * {@inheritDoc}
   */
  public function prepareRow(Row $row) {
    $system_data = $this->getSystemData();
    $password_policy_schema = $system_data['module']['password_policy']['schema_version'] ?? 0;
    $source_password_policy_version = 1;
    if ((int) $password_policy_schema >= 7200) {
      $source_password_policy_version = 2;
    }
    else {
      $source_data = $row->getSource();
      $password_policy_role = $this->select('password_policy_role', 'role')
        ->fields('role')
        ->condition('pid', $source_data['pid'])
        ->execute()
        ->fetchAll();
      $config['pid'] = $source_data['pid'];
      $config['name'] = $source_data['name'];
      $config['description'] = $source_data['description'];
      $config['enabled'] = $source_data['enabled'];
      $config['constraints'] = $source_data['constraints'];
      $config['created'] = $source_data['created'];
      $config['expiration'] = $source_data['expiration'];
      $config['warning'] = $source_data['warning'];
      $config['weight'] = $source_data['weight'];
      foreach ($password_policy_role as $role) {
        $config['role']['roles'][$role['rid']] = $role['rid'];
      }
      $row->setSourceProperty('config', serialize($config));
    }
    $row->setSourceProperty('source_version', $source_password_policy_version);
    return parent::prepareRow($row);
  }

}
