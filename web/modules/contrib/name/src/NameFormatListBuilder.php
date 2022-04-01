<?php

namespace Drupal\name;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\name\Entity\NameFormat;

/**
 * Name format list builder for the admin page.
 */
class NameFormatListBuilder extends ConfigEntityListBuilder {

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
   * The names that were used to generate the list.
   *
   * @var array
   */
  protected $names;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
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
   * @param \Drupal\name\NameFormatParser $parser
   *   The name format parser.
   * @param \Drupal\name\NameGeneratorInterface $generator
   *   The name generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, NameFormatParser $parser, NameGeneratorInterface $generator) {
    parent::__construct($entity_type, $storage);
    $this->parser = $parser;
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row = [];
    $row['label'] = $this->t('Label');
    $row['id'] = $this->t('Machine name');
    $row['format'] = $this->t('Format');
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
    $row['format'] = $entity->get('pattern');
    $row['examples'] = $this->examples($entity);
    $operations = $this->buildOperations($entity);
    $row['operations']['data'] = $operations;
    return $row;
  }

  /**
   * Provides some example based on names with various components set.
   *
   * @param \Drupal\name\Entity\NameFormat $entity
   *   The name format entity.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The example names with formatting applied.
   */
  protected function examples(NameFormat $entity) {
    $this->names = $this->generator->loadSampleValues(4);
    $examples = [];
    foreach ($this->names as $index => $example_name) {
      $formatted = $this->parser->parse($example_name, $entity->get('pattern'));
      if (!strlen($formatted)) {
        $formatted = $this->t('&lt;&lt;@empty&gt;&gt;', ['@empty' => $this->t('empty')]);
      }
      $examples[] = $this->t('(@num) %name', [
        '@num' => $index + 1,
        '%name' => $formatted,
      ]);
    }
    return new FormattableMarkup(implode('<br>', $examples), []);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'list' => parent::render(),
      'help' => $this->parser->renderableTokenHelp(),
    ];
  }

}
