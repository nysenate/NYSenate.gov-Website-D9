<?php

namespace Drupal\Tests\smart_sql_idmap\Unit\Plugin\migrate\id_map;

/**
 * Tests the Smart SQL ID map plugin with a long migration plugin ID.
 *
 * @group smart_sql_idmap
 */
class SmartSqlWithLongPluginIdTest extends SmartSqlTest {

  /**
   * {@inheritdoc}
   */
  protected $migrationConfiguration = [
    'id' => 'a_non_derived_migration_plugin_id_with_a_very_long_name',
  ];

  /**
   * {@inheritdoc}
   */
  protected $expectedMapTableName = 'm_map_a_non_derived_migration_plugin_id_with_a_very_long_name';

  /**
   * {@inheritdoc}
   */
  protected $expectedPrefixedMapTableName = 'm_map_a_non_derived_migration_plugin_i_6f26b5682a68dbd60';

  /**
   * {@inheritdoc}
   */
  protected $expectedMessageTableName = 'm_message_a_non_derived_migration_plugin_id_w_6f26b5682a68dbd60';

}
