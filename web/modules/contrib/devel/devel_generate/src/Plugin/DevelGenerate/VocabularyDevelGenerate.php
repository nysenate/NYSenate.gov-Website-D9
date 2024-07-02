<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a VocabularyDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "vocabulary",
 *   label = @Translation("vocabularies"),
 *   description = @Translation("Generate a given number of vocabularies. Optionally delete current vocabularies."),
 *   url = "vocabs",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 1,
 *     "title_length" = 12,
 *     "kill" = FALSE
 *   },
 *   dependencies = {
 *     "taxonomy",
 *   },
 * )
 */
class VocabularyDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The vocabulary storage.
   */
  protected VocabularyStorageInterface $vocabularyStorage;

  /**
   * Constructs a new VocabularyDevelGenerate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $string_translation,
    VocabularyStorageInterface $vocabulary_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $messenger, $language_manager, $module_handler, $string_translation);
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $entity_type_manager,
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $entity_type_manager->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of vocabularies?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of characters in vocabulary names'),
      '#default_value' => $this->getSetting('title_length'),
      '#required' => TRUE,
      '#min' => 2,
      '#max' => 255,
    ];
    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing vocabularies before generating new ones.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateElements(array $values): void {
    if ($values['kill']) {
      $this->deleteVocabularies();
      $this->setMessage($this->t('Deleted existing vocabularies.'));
    }

    $new_vocs = $this->generateVocabularies($values['num'], $values['title_length']);
    if (!empty($new_vocs)) {
      $this->setMessage($this->t('Created the following new vocabularies: @vocs', ['@vocs' => implode(', ', $new_vocs)]));
    }
  }

  /**
   * Deletes all vocabularies.
   */
  protected function deleteVocabularies(): void {
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    $this->vocabularyStorage->delete($vocabularies);
  }

  /**
   * Generates vocabularies.
   *
   * @param int $records
   *   Number of vocabularies to create.
   * @param int $maxlength
   *   (optional) Maximum length for vocabulary name.
   *
   * @return array
   *   Array containing the generated vocabularies id.
   */
  protected function generateVocabularies(int $records, int $maxlength = 12): array {
    $vocabularies = [];

    // Insert new data:
    for ($i = 1; $i <= $records; $i++) {
      $name = $this->getRandom()->word(mt_rand(2, $maxlength));

      $vocabulary = $this->vocabularyStorage->create([
        'name' => $name,
        'vid' => mb_strtolower($name),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'description' => "Description of $name",
        'hierarchy' => 1,
        'weight' => mt_rand(0, 10),
        'multiple' => 1,
        'required' => 0,
        'relations' => 1,
      ]);

      // Populate all fields with sample values.
      $this->populateFields($vocabulary);
      $vocabulary->save();

      $vocabularies[] = $vocabulary->id();
      unset($vocabulary);
    }

    return $vocabularies;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $values = [
      'num' => array_shift($args),
      'kill' => $options['kill'],
      'title_length' => 12,
    ];

    if ($this->isNumber($values['num']) == FALSE) {
      throw new \Exception(dt('Invalid number of vocabularies: @num.', ['@num' => $values['num']]));
    }

    return $values;
  }

}
