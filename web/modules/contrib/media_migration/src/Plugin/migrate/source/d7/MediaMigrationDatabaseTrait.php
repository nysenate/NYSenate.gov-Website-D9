<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\Core\Database\Connection;

/**
 * Trait for database related queries of Media Migration.
 */
trait MediaMigrationDatabaseTrait {

  /**
   * Returns a base query for plain files.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   * @param bool $distinct
   *   Base query should use distinct.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The base query.
   */
  protected function getFilePlainBaseQuery($connection = NULL, bool $distinct = TRUE) {
    $db = $connection ?? $this->getDatabase();
    assert($db instanceof Connection);
    $options = [
      'fetch' => \PDO::FETCH_ASSOC,
    ];
    $query = $db->select('file_managed', 'fm', $options);
    $query->distinct($distinct);
    $query->condition('fm.status', TRUE)
      ->condition('fm.uri', '', '<>');
    $query->addExpression($this->getSchemeExpression($db), 'scheme');
    $query->addExpression($this->getMainMimeTypeExpression($db), 'mime');
    $query->where("{$this->getSchemeExpression($db)} <> 'temporary'");

    // Omit all files that are used solely for a user picture and/or in webform
    // submission: they do not belong in Drupal's media library.
    $query->condition('fm.fid', $this->getUserPictureOnlyFidsQuery($db), 'NOT IN');
    $query->condition('fm.fid', $this->getWebformOrUserPictureOnlyFidsQuery($db), 'NOT IN');

    return $query;
  }

  /**
   * Returns a base query for file entity types.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   * @param bool $distinct
   *   Base query should use distinct.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The base query.
   */
  protected function getFileEntityBaseQuery($connection = NULL, bool $distinct = TRUE) {
    $db = $connection ?? $this->getDatabase();
    assert($db instanceof Connection);
    $options = [
      'fetch' => \PDO::FETCH_ASSOC,
    ];
    $query = $db->select('file_managed', 'fm', $options);
    if ($distinct) {
      $query->distinct();
    }
    $query->fields('fm', ['type'])
      ->condition('fm.status', TRUE)
      ->condition('fm.uri', 'temporary://%', 'NOT LIKE')
      ->condition('fm.uri', '', '<>')
      ->condition('fm.type', 'undefined', '<>');
    $query->addExpression($this->getSchemeExpression($db), 'scheme');

    // Omit all files that are used solely for a user picture: they do not
    // belong in Drupal's media library.
    $query->condition('fm.fid', $this->getUserPictureOnlyFidsQuery($db), 'NOT IN');
    $query->condition('fm.fid', $this->getWebformOrUserPictureOnlyFidsQuery($db), 'NOT IN');

    return $query;
  }

  /**
   * Returns the expression for the DB for getting the URI scheme.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression for the DB for getting the URI scheme.
   */
  protected function getSchemeExpression($connection = NULL) {
    $db = $connection ?? $this->getDatabase();
    assert($db instanceof Connection);
    return $this->dbIsSqLite($db)
      ? "SUBSTRING(fm.uri, 1, INSTR(fm.uri, '://') - 1)"
      : "SUBSTRING(fm.uri, 1, POSITION('://' IN fm.uri) - 1)";
  }

  /**
   * Returns the main MIME type's expression for the current DB.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression to get the main MIME type.
   */
  protected function getMainMimeTypeExpression($connection = NULL) {
    $db = $connection ?? $this->getDatabase();
    assert($db instanceof Connection);
    return $this->dbIsSqLite($db)
      ? "SUBSTRING(fm.filemime, 1, INSTR(fm.filemime, '/') - 1)"
      : "SUBSTRING(fm.filemime, 1, POSITION('/' IN fm.filemime) - 1)";
  }

  /**
   * Returns the file extension expression for the current DB.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression for getting the file extension.
   */
  protected function getExtensionExpression($connection = NULL) {
    $db = $connection ?? $this->getDatabase();
    assert($db instanceof Connection);

    return $this->dbIsSqLite($db)
      ? "REPLACE(fm.uri, RTRIM(fm.uri, REPLACE(fm.uri, '.', '')), '')"
      : "SUBSTRING(fm.uri FROM CHAR_LENGTH(fm.uri) - POSITION('.' IN REVERSE(fm.uri)) + 2)";
  }

  /**
   * Returns the subquery for the user picture-only file IDs.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query to get the FIDs of files that are used only as a user picture.
   */
  protected function getUserPictureOnlyFidsQuery(Connection $connection) {
    $query = $connection->select('users', 'u');
    $query->leftJoin('file_usage', 'fu', 'fu.fid = u.picture');
    $query->where('u.picture > 0');
    $query->fields('fu', ['fid']);
    $query->groupBy('fu.fid');
    $concat_expression = $this->dbIsPostgresql($connection)
      ? "STRING_AGG(DISTINCT fu.type, ',')"
      : "GROUP_CONCAT(DISTINCT fu.type)";
    $query->having("$concat_expression = :allowed_value_user_only OR $concat_expression = :allowed_value_user_webform_only OR $concat_expression = :allowed_value_webform_user_only", [
      ':allowed_value_user_only' => 'user',
      ':allowed_value_user_webform_only' => 'user,webform',
      ':allowed_value_webform_user_only' => 'webform,user',
    ]);

    return $query;
  }

  /**
   * Subquery for FIDs used only in webform submissions and/or by user entities.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Query that gets the FIDs of files used only in webform submissions.
   */
  protected function getWebformOrUserPictureOnlyFidsQuery(Connection $connection) {
    $query = $connection->select('file_usage', 'fu');
    $query->fields('fu', ['fid']);
    $query->groupBy('fu.fid');
    $wf_type_concat_expression = $this->dbIsPostgresql($connection)
      ? "STRING_AGG(DISTINCT fu.type, ',')"
      : "GROUP_CONCAT(DISTINCT fu.type)";
    $query->having("$wf_type_concat_expression = :allowed_type_submission_only OR $wf_type_concat_expression = :allowed_type_submission_user_only OR $wf_type_concat_expression = :allowed_type_user_submission_only", [
      ':allowed_type_submission_only' => 'submission',
      ':allowed_type_submission_user_only' => 'submission,user',
      ':allowed_type_user_submission_only' => 'user,submission',
    ]);

    return $query;
  }

  /**
   * Determines whether the connection is a PostgeSQL connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to check.
   *
   * @return bool
   *   Whether the connection is a PostgeSQL connection.
   */
  protected function dbIsPostgresql(Connection $connection): bool {
    return ($connection->getConnectionOptions()['driver'] ?? NULL) === 'pgsql';
  }

  /**
   * Determines whether the connection is a SQLite connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to check.
   *
   * @return bool
   *   Whether the connection is a SQLite connection.
   */
  protected function dbIsSqLite(Connection $connection): bool {
    $connection_options = $connection->getConnectionOptions();
    return ($connection_options['driver'] ?? NULL) === 'sqlite' ||
      // For in-memory connections.
      preg_match('/\bsqlite\b/', $connection_options['namespace'] ?? '');
  }

}
