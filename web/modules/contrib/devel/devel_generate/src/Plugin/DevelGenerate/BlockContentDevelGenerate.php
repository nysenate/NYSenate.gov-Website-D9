<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\block_content\BlockContentInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BlockContentDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "block_content",
 *   label = @Translation("Block Content"),
 *   description = @Translation("Generate a given number of Block content blocks. Optionally delete current blocks."),
 *   url = "block-content",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "title_length" = 4,
 *     "add_type_label" = FALSE,
 *     "reusable" = TRUE
 *   },
 * )
 */
class BlockContentDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The block content storage.
   */
  protected EntityStorageInterface $blockContentStorage;

  /**
   * The block content type storage.
   */
  protected EntityStorageInterface $blockContentTypeStorage;

  /**
   * The extension path resolver service.
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * The entity type bundle info service.
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The content translation manager.
   */
  protected ?ContentTranslationManagerInterface $contentTranslationManager;

  /**
   * The Drush batch flag.
   */
  protected bool $drushBatch = FALSE;

  /**
   * Constructs a BlockContentDevelGenerate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin definition.
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
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The block content storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The block content type storage.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface|null $content_translation_manager
   *   The content translation manager service.
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
    EntityStorageInterface $block_content_storage,
    EntityStorageInterface $block_content_type_storage,
    ExtensionPathResolver $extensionPathResolver,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ContentTranslationManagerInterface $content_translation_manager = NULL
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $messenger, $language_manager, $module_handler, $string_translation);
    $this->blockContentStorage = $block_content_storage;
    $this->blockContentTypeStorage = $block_content_type_storage;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->contentTranslationManager = $content_translation_manager;
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
      $entity_type_manager->getStorage('block_content'),
      $entity_type_manager->getStorage('block_content_type'),
      $container->get('extension.path.resolver'),
      $container->get('entity_type.bundle.info'),
      $container->has('content_translation.manager') ? $container->get('content_translation.manager') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $blockTypes = $this->blockContentTypeStorage->loadMultiple();
    $options = [];

    foreach ($blockTypes as $type) {
      $options[$type->id()] = [
        'type' => [
          'label' => $type->label(),
          'description' => $type->getDescription(),
        ],
      ];
    }

    $header = [
      'type' => $this->t('Block Content type'),
      'description' => $this->t('Description'),
    ];

    $form['block_types'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all content</strong> in these block types before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many blocks would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $form['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in block descriptions'),
      '#default_value' => $this->getSetting('title_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];

    $form['skip_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fields to leave empty'),
      '#description' => $this->t('Enter the field names as a comma-separated list. These will be skipped and have a default value in the generated content.'),
      '#default_value' => NULL,
    ];
    $form['base_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base fields to populate'),
      '#description' => $this->t('Enter the field names as a comma-separated list. These will be populated.'),
      '#default_value' => NULL,
    ];

    $form['reusable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reusable blocks'),
      '#description' => $this->t('This will mark the blocks to be created as reusable.'),
      '#default_value' => $this->getSetting('reusable'),

    ];
    $form['add_type_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefix the title with the block type label.'),
      '#description' => $this->t('This will not count against the maximum number of title words specified above.'),
      '#default_value' => $this->getSetting('add_type_label'),
    ];

    // Add the language and translation options.
    $form += $this->getLanguageForm('blocks');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state): void {
    if (!array_filter($form_state->getValue('block_types'))) {
      $form_state->setErrorByName('block_types', $this->t('Please select at least one block type'));
    }
    $skip_fields = is_null($form_state->getValue('skip_fields')) ? [] : self::csvToArray($form_state->getValue('skip_fields'));
    $base_fields = is_null($form_state->getValue('base_fields')) ? [] : self::csvToArray($form_state->getValue('base_fields'));
    $form_state->setValue('skip_fields', $skip_fields);
    $form_state->setValue('base_fields', $base_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $add_language = self::csvToArray($options['languages']);
    // Intersect with the enabled languages to make sure the language args
    // passed are actually enabled.
    $valid_languages = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
    $values['add_language'] = array_intersect($add_language, $valid_languages);

    $translate_language = self::csvToArray($options['translations']);
    $values['translate_language'] = array_intersect($translate_language, $valid_languages);

    $values['add_type_label'] = $options['add-type-label'];
    $values['kill'] = $options['kill'];
    $values['feedback'] = $options['feedback'];
    $values['skip_fields'] = is_null($options['skip-fields']) ? [] : self::csvToArray($options['skip-fields']);
    $values['base_fields'] = is_null($options['base-fields']) ? [] : self::csvToArray($options['base-fields']);
    $values['title_length'] = 6;
    $values['num'] = array_shift($args);
    $values['max_comments'] = array_shift($args);

    $all_types = array_keys($this->blockContentGetBundles());
    $selected_types = self::csvToArray($options['block_types']);

    if (empty($selected_types)) {
      throw new \Exception(dt('No Block content types available'));
    }

    $values['block_types'] = array_combine($selected_types, $selected_types);
    $block_types = array_filter($values['block_types']);

    if (!empty($values['kill']) && empty($block_types)) {
      throw new \Exception(dt('To delete content, please provide the Block content types (--bundles)'));
    }

    // Checks for any missing block content types before generating blocks.
    if (array_diff($block_types, $all_types)) {
      throw new \Exception(dt('One or more block content types have been entered that don\'t exist on this site'));
    }

    if ($this->isBatch($values['num'])) {
      $this->drushBatch = TRUE;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    if ($this->isBatch($values['num'])) {
      $this->generateBatchContent($values);
    }
    else {
      $this->generateContent($values);
    }
  }

  /**
   * Generate content in batch mode.
   *
   * This method is used when the number of elements is 50 or more.
   */
  private function generateBatchContent(array $values): void {
    // Remove unselected block content types.
    $values['block_types'] = array_filter($values['block_types']);
    // If it is drushBatch then this operation is already run in the
    // self::validateDrushParams().
    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = ['devel_generate_operation',
        [$this, 'batchContentKill', $values],
      ];
    }

    // Add the operations to create the blocks.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = ['devel_generate_operation',
        [$this, 'batchContentAddBlock', $values],
      ];
    }

    // Set the batch.
    $batch = [
      'title' => $this->t('Generating Content'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => $this->extensionPathResolver->getPath('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];

    batch_set($batch);
    if ($this->drushBatch) {
      drush_backend_batch_process();
    }
  }

  /**
   * Batch wrapper for calling ContentAddBlock.
   */
  public function batchContentAddBlock(array $vars, array &$context): void {
    if (!isset($context['results']['num'])) {
      $context['results']['num'] = 0;
    }
    if ($this->drushBatch) {
      $context['results']['num']++;
      $this->develGenerateContentAddBlock($vars);
    }
    else {
      $context['results'] = $vars;
      $this->develGenerateContentAddBlock($context['results']);
    }
    if (!empty($vars['num_translations'])) {
      $context['results']['num_translations'] += $vars['num_translations'];
    }
  }

  /**
   * Batch wrapper for calling ContentKill.
   */
  public function batchContentKill(array $vars, &$context): void {
    if ($this->drushBatch) {
      $this->contentKill($vars);
    }
    else {
      $context['results'] = $vars;
      $this->contentKill($context['results']);
    }
  }

  /**
   * Generate content when not in batch mode.
   *
   * This method is used when the number of elements is under 50.
   */
  private function generateContent(array $values): void {
    $values['block_types'] = array_filter($values['block_types']);
    if (!empty($values['kill']) && $values['block_types']) {
      $this->contentKill($values);
    }

    if (!empty($values['block_types'])) {
      $start = time();
      $values['num_translations'] = 0;
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->develGenerateContentAddBlock($values);
        if (isset($values['feedback']) && $i % $values['feedback'] == 0) {
          $now = time();
          $options = [
            '@feedback' => $values['feedback'],
            '@rate' => ($values['feedback'] * 60) / ($now - $start),
          ];
          $this->messenger->addStatus(dt('Completed @feedback blocks (@rate blocks/min)', $options));
          $start = $now;
        }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], 'Created 1 block', 'Created @count blocks'));
    if ($values['num_translations'] > 0) {
      $this->setMessage($this->formatPlural($values['num_translations'], 'Created 1 block translation', 'Created @count block translations'));
    }
  }

  /**
   * Create one block. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   Results information.
   */
  protected function develGenerateContentAddBlock(array &$results): void {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }

    $block_type = array_rand($results['block_types']);

    // Add the block type label if required.
    $title_prefix = $results['add_type_label'] ? $this->blockContentTypeStorage->load($block_type)->label() . ' - ' : '';

    $values = [
      'info' => $title_prefix . $this->getRandom()->sentences(mt_rand(1, $results['title_length']), TRUE),
      'type' => $block_type,
    ];

    if (isset($results['add_language'])) {
      $values['langcode'] = $this->getLangcode($results['add_language']);
    }
    if (isset($results['reusable'])) {
      $values['reusable'] = (int) $results['reusable'];
    }
    $block = $this->blockContentStorage->create($values);

    // A flag to let hook_block_content_insert() implementations
    // know that this is a generated block.
    $block->devel_generate = $results;

    // Populate non-skipped fields with sample values.
    $this->populateFields($block, $results['skip_fields'], $results['base_fields']);

    // Remove the fields which are intended to have no value.
    foreach ($results['skip_fields'] as $field) {
      unset($block->$field);
    }

    // See devel_generate_entity_insert() for actions that happen before and
    // after this save.
    $block->save();

    // Add translations.
    if (isset($results['translate_language']) && !empty($results['translate_language'])) {
      $this->develGenerateContentAddBlockTranslation($results, $block);
    }
  }

  /**
   * Create translation for the given block.
   *
   * @param array $results
   *   Results array.
   * @param \Drupal\block_content\BlockContentInterface $block
   *   Block to add translations to.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function develGenerateContentAddBlockTranslation(array &$results, BlockContentInterface $block): void {
    if (is_null($this->contentTranslationManager)) {
      return;
    }
    if (!$this->contentTranslationManager->isEnabled('block_content', $block->bundle())) {
      return;
    }
    if ($block->langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED || $block->langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      return;
    }

    if (!isset($results['num_translations'])) {
      $results['num_translations'] = 0;
    }
    // Translate the block to each target language.
    $skip_languages = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      $block->langcode->value,
    ];
    foreach ($results['translate_language'] as $langcode) {
      if (in_array($langcode, $skip_languages)) {
        continue;
      }
      $translation_block = $block->addTranslation($langcode);
      $translation_block->devel_generate = $results;
      $translation_block->setInfo($block->label() . ' (' . $langcode . ')');
      $this->populateFields($translation_block);
      $translation_block->save();

      $results['num_translations']++;
    }
  }

  /**
   * Deletes all blocks of given block content types.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function contentKill(array $values): void {
    $bids = $this->blockContentStorage->getQuery()
      ->condition('type', $values['block_types'], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($bids)) {
      $blocks = $this->blockContentStorage->loadMultiple($bids);
      $this->blockContentStorage->delete($blocks);
      $this->setMessage($this->t('Deleted %count blocks.', ['%count' => count($bids)]));
    }
  }

  /**
   * Determines if the content should be generated in batch mode.
   */
  protected function isBatch($content_count): bool {
    return $content_count >= 50;
  }

  /**
   * Returns a list of available block content type names.
   *
   * This list can include types that are queued for addition or deletion.
   *
   * @return string[]
   *   An array of block content type labels,
   *   keyed by the block content type name.
   */
  public function blockContentGetBundles(): array {
    return array_map(fn($bundle_info) => $bundle_info['label'], $this->entityTypeBundleInfo->getBundleInfo('block_content'));
  }

}
