# Queue Unique
Did you ever want a queue that only accepts unique items? This module provides a way of doing that. If you try to insert a duplicated item in the queue, the item is ignored.

```php
// $data can be anything.
$data = array('Lorem', 'ipsum');

$queue_name = 'your_queue_name';
$queue = \Drupal::service('queue')->get($queue_name);
$queue->createItem($data);
// This will insert a duplicate, and will return FALSE.
if ($queue->createItem($data) === FALSE) {
  // The item was a duplicate, respond appropriately.
}
```

## Basic Usage
In order for your queue to use Queue Unique with the default queue service
(the core QueueFactory) you need to update your `settings.php` file:

```php
$settings['queue_service_your_queue_name'] = 'queue_unique.database';
```

Otherwise, you need to specifically get this module's database queue factory service:

```php
$queue_name = 'your_queue_name';
$queue = \Drupal::service('queue_unique.database')->get($queue_name);
$queue->createItem($data);
```

## Advanced Usage

This module provides an alternative QueueFactory class. Replace the core QueueFactory
using, for example, a site services.yaml file with an entry like this:


```
services:
  queue:
    class: Drupal\queue_unique\QueueFactory
    arguments: ['@settings']
    calls:
      - [setContainer, ['@service_container']]
```

See sites/default/default.services.yml and `$settings['container_yamls']` in default.settings.php.

When the QueueFactory is replaced, you can get a unique queue based on a prefix on the queue
name of `queue_unique.`

For example:

```php
$queue_name = 'queue_unique/your_queue_name';
$queue = \Drupal::service('queue')->get($queue_name);
$queue->createItem($data);
```

The actual queue name in the database will be `your_queue_name`.

This is especially useful with a queue worker plugin, e.g. extending `\Drupal\Core\Queue\QueueWorkerBase`

If you name the plugin ID with this prefix it can be processed
on cron automatically and correctly pull items from the unique queue:

```php
namespace Drupal\mymodule\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Queue worker to handle when something has changed.
 *
 * @QueueWorker(
 *   id = "queue_unique/mymodule_entity_update",
 *   title = @Translation("Handle entity create or update."),
 *   cron = {"time" = 20}
 * )
 */
class EntityUpdateQueueWorker extends QueueWorkerBase {
}
```

Add items to the `mymodule_entity_update` unique queue and they will be processed on
cron. See \Drupal\Core\Cron::processQueues().

For example:

```
$queue = \Drupal::service('queue')->get("queue_unique/mymodule_entity_update");
$queue->createItem($data);
```

or:

```php
$queue = \Drupal::service('queue_unique.database')->get('mymodule_entity_update');
$queue->createItem($data);

```
