<?php

namespace Drupal\name;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\name\Entity\NameListFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * The name list builder.
 */
class NameListFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatterInterface
   */
  protected $formatter;

  /**
   * The name format parser.
   *
   * @var \Drupal\name\NameFormatParser
   */
  protected $parser;

  /**
   * The name generator.
   *
   * @var \Drupal\name\NameGeneratorInterface
   */
  protected $generator;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('name.formatter'),
      $container->get('name.format_parser'),
      $container->get('name.generator')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\name\NameFormatterInterface $formatter
   *   The name formatter.
   * @param \Drupal\name\NameFormatParser $parser
   *   The name format parser.
   * @param \Drupal\name\NameGeneratorInterface $generator
   *   The name generator service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, NameFormatterInterface $formatter, NameFormatParser $parser, NameGeneratorInterface $generator) {
    parent::__construct($entity_type, $storage);
    $this->parser = $parser;
    $this->formatter = $formatter;
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row = [];
    $row['label'] = $this->t('Label');
    $row['id'] = $this->t('Machine name');
    $row['settings'] = $this->t('Settings');
    $row['examples'] = $this->t('Examples');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    $settings = $entity->listSettings();

    $and_options = $this->formatter->getLastDelimitorTypes();
    $and_delimiter = isset($and_options[$settings['and']]) ? $and_options[$settings['and']] : $this->t('-- invalid option --');

    $and_behavior_options = $this->formatter->getLastDelimitorBehaviors(FALSE);
    $and_behavior = isset($and_behavior_options[$settings['delimiter_precedes_last']])
        ? $and_behavior_options[$settings['delimiter_precedes_last']]
        : $this->t('-- invalid option --');
    if ($settings['el_al_min']) {
      $behavior = $this->t('Reduce after @max items and show @min items followed by <em>el al</em>.', [
        '@max' => $settings['el_al_min'],
        '@min' => $settings['el_al_first'],
      ]);
    }
    else {
      $behavior = $this->t('Show all names.');
    }

    $summary = [
      $behavior,
      $this->t('Delimiters: "@delimiter" and @last', [
        '@delimiter' => $settings['delimiter'],
        '@last' => $and_delimiter,
      ]),
      $this->t('Last delimiter: @delimiter', ['@delimiter' => $and_behavior]),
    ];
    if ($entity->isLocked()) {
      $summary[] = t('Default format (locked)');
    }
    $row['settings'] = new FormattableMarkup(implode('<br>', $summary), []);

    // Add a few examples.
    $row['examples'] = $this->examples($entity);

    $operations = $this->buildOperations($entity);
    $row['operations']['data'] = $operations;
    return $row;
  }

  /**
   * Provides some example based on names with various components set.
   *
   * @param \Drupal\name\Entity\NameListFormat $entity
   *   The name format entity.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The example names with formatting applied.
   */
  protected function examples(NameListFormat $entity) {
    $examples = [];
    foreach ([1, 2, 3, 4] as $num) {
      $names = $this->generator->generateSampleNames($num);
      $examples[] = $this->t('(@num) %list', [
        '@num' => $num,
        '%list' => $this->formatter->formatList($names, 'family', $entity->id()),
      ]);
    }
    return new FormattableMarkup(implode('<br>', $examples), []);
  }

}
