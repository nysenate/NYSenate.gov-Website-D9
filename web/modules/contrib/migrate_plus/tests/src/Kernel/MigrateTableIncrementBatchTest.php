<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

/**
 * Verifies all tests pass with batching enabled, uneven batches.
 *
 * @group migrate
 */
final class MigrateTableIncrementBatchTest extends MigrateTableIncrementTest {

  /**
   * The batch size to configure.
   *
   * @var int
   */
  protected int $batchSize = 2;

}
