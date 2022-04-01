<?php

namespace Drupal\Tests\field_validation\Kernel\Plugin\FieldValidationRule;

/**
 * Tests BlacklistFieldValidationRule.
 *
 * @group field_validation
 *
 * @package Drupal\Tests\field_validation\Kernel
 */
class BlackListFieldValidationRuleTest extends FieldValidationRuleBase {

  /**
   * Stores blacklisted words.
   *
   * @var array
   */
  private $blacklisted = [
    'bug',
    'issue',
    'patch',
  ];

  /**
   * Stores whitelisted words.
   *
   * @var array
   */
  private $whitelisted = [
    'release',
    'drupal',
    'docs',
  ];

  /**
   * Stores mock ruleset.
   *
   * @var \Drupal\field_validation\Entity\FieldValidationRuleSet
   */
  protected $ruleSet;

  /**
   * Entity interface.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Field name.
   */
  const FIELD_NAME = 'field_blacklist_text';

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupTestArticle(self::FIELD_NAME);

    $this->ruleSet = $this->ruleSetStorage->create([
      'name' => 'Blacklist_test',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $this->ruleSet->addFieldValidationRule([
      'id' => 'blacklist_field_validation_rule',
      'title' => 'validation rule blacklist',
      'weight' => 1,
      'field_name' => self::FIELD_NAME,
      'column' => 'value',
      'error_message' => 'Blacklisted words are in field',
      'data' => [
        'setting' => implode(',', $this->blacklisted),
      ],
    ]);
    $this->ruleSet->save();

    $this->entity = $this->nodeStorage->create([
      'type' => 'article',
      'title' => 'test',
      self::FIELD_NAME => $this->blacklisted[array_rand($this->blacklisted)],
    ]);
    $this->entity->get(self::FIELD_NAME)
      ->getFieldDefinition()
      ->addConstraint(
        'FieldValidationConstraint',
        ['ruleset_name' => $this->ruleSet->getName()]
      );
  }

  /**
   * Tests BlacklistFieldValidationRule.
   */
  public function testBlacklistRule() {
    $this->assertConstraintFail(
      $this->entity,
      self::FIELD_NAME,
      $this->blacklisted[array_rand($this->blacklisted)],
      $this->ruleSet
    );

    $this->assertConstraintFail(
      $this->entity,
      self::FIELD_NAME,
      implode(',', $this->blacklisted),
      $this->ruleSet
    );

    $this->assertConstraintPass(
      $this->entity,
      self::FIELD_NAME,
      $this->whitelisted[array_rand($this->whitelisted)]
    );

    $this->assertConstraintPass(
      $this->entity,
      self::FIELD_NAME,
      implode(',', $this->whitelisted)
    );
  }

}
