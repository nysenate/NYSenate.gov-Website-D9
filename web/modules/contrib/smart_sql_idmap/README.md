# SMART SQL ID MAP

This module provides a Sql migrate ID map plugin as work-around for core issues:
- [#2845340: migrate mapping & messages table names are truncated, can lead to incorrect mapping lookups][1]
- [#3227549: Sql id map plugin's getRowByDestination shouldn't return FALSE][2]
- [#3227660: MigrateExecutable::rollback incorrectly assumes MigrateMapInterface::getRowByDestination() returns an array with 'rollback_action' key][3]


## Usage

You only have to add this to your migration plugin:

```yaml
idMap:
  plugin: smart_sql
```

So, at the end, you should have something like this:
```yaml
id: d7_tracker_settings
label: Tracker settings
migration_tags:
  - Drupal 7
  - Configuration
idMap:
  plugin: smart_sql
source:
  plugin: variable
  variables_required:
    - tracker_batch_size
process:
  cron_index_limit: tracker_batch_size
destination:
  plugin: config
  config_name: tracker.settings
```

*I will mark this module as obsolete when every supported Drupal 8|9 core
version will contain the fix for all of the issues.*

[1]: https://drupal.org/i/2845340
[2]: https://drupal.org/i/3227549
[3]: https://drupal.org/i/3227660
