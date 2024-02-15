<?php

namespace Drupal\Tests\media_migration\Traits;

/**
 * Trait for working around and testing Drupal core regression #3260111.
 */
trait Issue3260111FixedTrait {

  /**
   * Checks whether the actual DB connection is a SQLite connection.
   *
   * @return bool
   *   Whether the actual DB connection is a SQLite connection.
   */
  protected function connectionIsSqlite(): bool {
    return \Drupal::database()->getConnectionOptions()['driver'] === 'sqlite';
  }

  /**
   * Determines whether the current Drupal core has the regression.
   *
   * @return bool
   *   Whether the available core version is affected or not.
   */
  protected function coreVersionMightHaveRegression3260111(): bool {
    // 9.2 and below: not affected.
    // 9.3 and above: regression present.
    $core_major_minor = implode(
      '.',
      [
        explode('.', \Drupal::VERSION)[0],
        explode('.', \Drupal::VERSION)[1],
      ]
    );
    return version_compare($core_major_minor, '9.3', 'ge');
  }

}
