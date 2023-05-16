<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\FieldSettings;

/**
 * Checks for Javascript and PHP in submitted content.
 */
class Field extends Check {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->settings = new FieldSettings($this, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Dangerous tags in content exclude list';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'field';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];
    $hushed_findings = [];

    $field_types = [
      'text_with_summary',
      'text_long',
    ];
    $tags = [
      'Javascript' => 'script',
      'PHP' => '?php',
    ];

    $known_risky_fields = $this->settings()->get('known_risky_fields', []);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');
    foreach ($field_manager->getFieldMap() as $entity_type_id => $fields) {
      $field_storage_definitions = $field_manager->getFieldStorageDefinitions($entity_type_id);
      foreach ($fields as $field_name => $field) {
        if (!isset($field_storage_definitions[$field_name])) {
          continue;
        }
        $field_storage_definition = $field_storage_definitions[$field_name];
        if (in_array($field_storage_definition->getType(), $field_types)) {
          $entity = $entity_type_manager->getStorage($entity_type_id)
            ->getEntityType();

          $separator = '_';
          $table = '';
          $id = 'entity_id';
          // We only check entities that are stored in database.
          if (is_a($entity->getStorageClass(), SqlContentEntityStorage::class, TRUE)) {
            if ($field_storage_definition instanceof FieldStorageConfig) {
              $table = $entity_type_id . '__' . $field_name;
            }
            else {
              $translatable = $entity->isTranslatable();
              if ($translatable) {
                $table = $entity->getDataTable() ?: $entity_type_id . '_field_data';
              }
              else {
                $table = $entity->getBaseTable() ?: $entity_type_id;
              }
              $separator = '__';
              $id = $entity->getKey('id');
            }
          }
          foreach (array_keys($field_storage_definition->getSchema()['columns']) as $column) {
            $column_name = $field_name . $separator . $column;
            foreach ($tags as $vulnerability => $tag) {
              $query = $this->database()->query("SELECT `$id`, `$column_name` FROM {$table} t");
              $record = $query->fetchAssoc();;
              // foreach ($query->fetchAssoc() as $id_value => $column_value) {
              while ($record = $query->fetchAssoc()) {
                $column_value = $record[$column_name];
                $id_value = $record[$id];
                if ($id_value === 150003) {
                  $test = 0;
                }
                if (strpos((string) $column_value, '<' . $tag) !== FALSE) {
                  // Only alert on values that are not known to be safe.
                  $hash = hash('sha256', implode(
                    [
                      $entity_type_id,
                      $id_value,
                      $field_name,
                      $column_value,
                    ]
                  ));
                  if (!array_key_exists($hash, $known_risky_fields)) {
                    // Vulnerability found.
                    $findings[$entity_type_id][$id_value][$field_name][] = $vulnerability;
                    $findings[$entity_type_id][$id_value][$field_name]['hash'] = $hash;
                  }
                  else {
                    $hushed_findings[$entity_type_id][$id_value][$field_name][] = $vulnerability;
                    $hushed_findings[$entity_type_id][$id_value][$field_name]['hash'] = $hash;
                    $hushed_findings[$entity_type_id][$id_value][$field_name]['reason'] = $known_risky_fields[$hash];
                  }
                }
              }
              unset($query);
            }
          }
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings, TRUE, NULL, $hushed_findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('Script and PHP code in content does not align with Drupal best practices and may be a vulnerability if an untrusted user is allowed to edit such content. It is recommended you remove such contents or add to exclude list in security review settings page.');

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Dangerous tags in content'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    $hushed = $result->hushedFindings();
    if (empty($findings) && empty($hushed)) {
      return [];
    }

    $paragraphs = [];
    $paragraphs[] = $this->t('The following items potentially have dangerous tags.');

    $items = $this->loopThroughItems($findings);
    $hushed_items = $this->loopThroughItems($hushed, TRUE);

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items,
      '#hushed_items' => $hushed_items,
    ];
  }

  /**
   * Attempt to get a good link for the given entity.
   *
   * Falls back on a string with entity type id and id if no good link can
   * be found.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Entity link.
   */
  protected function getEntityLink(EntityInterface $entity) {
    try {
      $url = $entity->toUrl('edit-form');
    }
    catch (UndefinedLinkTemplateException $e) {
      $url = NULL;
    }
    if ($url === NULL) {
      try {
        $url = $entity->toUrl();
      }
      catch (UndefinedLinkTemplateException $e) {
        $url = NULL;
      }
    }

    return $url !== NULL ? $url->toString() : ($entity->getEntityTypeId() . ':' . $entity->id());
  }

  /**
   * Loop through the next array of the field findings/hushed_findings.
   *
   * @param array $list
   *   Findings list to loop through.
   * @param bool $additional_info
   *   If there is additional information that should be added to output.
   *
   * @return array
   *   Formatted findings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loopThroughItems(array $list, bool $additional_info = FALSE): array {
    $items = [];
    if (!empty($list)) {
      foreach ($list as $entity_type_id => $entities) {
        foreach ($entities as $entity_id => $fields) {
          $entity = $this->entityTypeManager()
            ->getStorage($entity_type_id)
            ->load($entity_id);

          foreach ($fields as $field => $finding) {
            $hash = $finding['hash'];
            unset($finding['hash']);
            if ($additional_info) {
              $items[] = $this->t(
                '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash | <strong>Reason is @reason</strong>',
                [
                  '@vulnerabilities' => $finding[0],
                  '@field' => $field,
                  '@label' => $entity->label(),
                  ':url' => $this->getEntityLink($entity),
                  '@hash' => $hash,
                  '@reason' => $finding['reason'],
                ]
              );
            }
            else {
              $items[] = $this->t(
                '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash',
                [
                  '@vulnerabilities' => implode(' and ', $finding),
                  '@field' => $field,
                  '@label' => $entity->label(),
                  ':url' => $this->getEntityLink($entity),
                  '@hash' => $hash,
                ]
              );
            }
          }
        }
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = '';
    foreach ($findings as $entity_type_id => $entities) {
      foreach ($entities as $entity_id => $fields) {
        $entity = $this->entityTypeManager()
          ->getStorage($entity_type_id)
          ->load($entity_id);

        foreach ($fields as $field => $finding) {
          $output .= "\t" . $this->t(
              '@vulnerabilities in @field of :link',
              [
                '@vulnerabilities' => implode(' and ', $finding),
                '@field' => $field,
                ':link' => $this->getEntityLink($entity),
              ]
            ) . "\n";
        }
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Dangerous tags were not found in any submitted content (fields).');

      case CheckResult::FAIL:
        return $this->t('Dangerous tags were found in submitted content (fields).');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
