<?php

namespace Drupal\name;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * The name option provider for the name field.
 */
class NameOptionsProvider {

  /**
   * The regular expression for finding the vocabulary token.
   *
   * @var string
   */
  const vocabularyRegExp = '/^\[vocabulary:([0-9a-z\_]{1,})\]/';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The term storage manager.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The vocab storage manager.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Constructs a new NameOptionsProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;

    if ($this->entityTypeManager && $this->moduleHandler->moduleExists('taxonomy')) {
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      $this->vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    }
  }

  /**
   * Options for a name component.
   */
  public function getOptions(FieldDefinitionInterface $field, $component) {
    $fs = $field->getSettings();
    $options = $fs[$component . '_options'];
    foreach ($options as $index => $opt) {
      if (preg_match(self::vocabularyRegExp, trim($opt), $matches)) {
        unset($options[$index]);
        if ($this->termStorage && $this->vocabularyStorage) {
          $vocabulary = $this->vocabularyStorage->load($matches[1]);
          if ($vocabulary) {
            $max_length = isset($fs['max_length'][$component]) ? $fs['max_length'][$component] : 255;
            foreach ($this->termStorage->loadTree($vocabulary->id()) as $term) {
              if (mb_strlen($term->name) <= $max_length) {
                $options[] = $term->name;
              }
            }
          }
        }
      }
    }

    // Options could come from multiple sources, filter duplicates.
    $options = array_unique($options);

    if (isset($fs['sort_options']) && !empty($fs['sort_options'][$component])) {
      natcasesort($options);
    }
    $default = FALSE;
    foreach ($options as $index => $opt) {
      if (strpos($opt, '--') === 0) {
        unset($options[$index]);
        $default = trim(mb_substr($opt, 2));
      }
    }
    $options = array_map('trim', $options);
    $options = array_combine($options, $options);
    if ($default !== FALSE) {
      $options = ['' => $default] + $options;
    }
    return $options;
  }

}
