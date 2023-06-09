<?php

namespace Drupal\nys_accumulator;

/**
 * Defines events for nys_accumulator module.
 */
final class Events {

  /**
   * Fires prior to saving an accumulator entry in the database.
   */
  const PRE_SAVE = 'Drupal\\nys_accumulator\\Event\\PreSaveEvent';

  /**
   * Fires as the module detects a first-time login from hook_user_login().
   *
   * @see nys_accumulator_user_login()
   */
  const FIRST_LOGIN = 'Drupal\\nys_accumulator\\Event\\FirstLoginEvent';

  /**
   * Fires as the module detects a new vote from hook_entity_presave().
   *
   * @see nys_accumulator_entity_presave()
   */
  const VOTE_CAST = 'Drupal\\nys_accumulator\\Event\\VoteCastEvent';

  /**
   * Fires as the module detects a new vote from hook_entity_presave().
   *
   * @see nys_accumulator_entity_presave()
   */
  const USER_EDIT = 'Drupal\\nys_accumulator\\Event\\UserEditEvent';

  /**
   * Fires as the module detects a new vote from hook_entity_presave().
   *
   * @see nys_accumulator_entity_presave()
   */
  const SUBMIT_QUESTION = 'Drupal\\nys_accumulator\\Event\\SubmitQuestionEvent';

  /**
   * Gets the array of all event constants.
   */
  public static function getEvents(): array {
    return (new \ReflectionClass(__CLASS__))->getConstants();
  }

}
