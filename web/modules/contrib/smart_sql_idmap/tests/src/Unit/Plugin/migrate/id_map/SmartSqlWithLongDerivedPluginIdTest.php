<?php

namespace Drupal\Tests\smart_sql_idmap\Unit\Plugin\migrate\id_map;

/**
 * Tests the Smart SQL ID map plugin with a long derived migration plugin ID.
 *
 * @group smart_sql_idmap
 */
class SmartSqlWithLongDerivedPluginIdTest extends SmartSqlTest {

  /**
   * {@inheritdoc}
   */
  protected $migrationConfiguration = [
    'id' => 'a_derived_migration_plugin_id:with_a_very_very_very_long_name',
  ];

  /**
   * {@inheritdoc}
   */
  protected $expectedMapTableName = 'm_map_a_derived_migration_plugin_id__with_a_v_5eb65dc9e1899f7a4';

  /**
   * {@inheritdoc}
   */
  protected $expectedPrefixedMapTableName = 'm_map_a_derived_migration_plugin_id__w_5eb65dc9e1899f7a4';

  /**
   * {@inheritdoc}
   */
  protected $expectedMessageTableName = 'm_message_a_derived_migration_plugin_id__with_5eb65dc9e1899f7a4';

}
