<?php

namespace Drupal\simple_sitemap_engines\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search engine entity list builder.
 */
class SearchEngineListBuilder extends ConfigEntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * SearchEngineListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              DateFormatterInterface $date_formatter,
                              StateInterface $state) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['url'] = $this->t('Submission URL');
    $header['variants'] = $this->t('Sitemap variants');
    $header['last_submitted'] = $this->t('Last submitted');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $last_submitted = $this->state->get("simple_sitemap_engines.simple_sitemap_engine.{$entity->id()}.last_submitted", -1);

    /** @var \Drupal\simple_sitemap_engines\Entity\SearchEngine $entity */
    $row['label'] = $entity->label();
    $row['url'] = $entity->url;
    $row['variants'] = implode(', ', $entity->sitemap_variants);
    $row['last_submitted'] = $last_submitted !== -1
      ? $this->dateFormatter->format($last_submitted, 'short')
      : $this->t('Never');

    return $row;
  }

  /**
   * Build the render array.
   */
  public function render() {
    return [
      'simple_sitemap_engines' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#prefix' => FormHelper::getDonationText(),
        '#title' => $this->t('Submission status'),
        'table' => parent::render(),
        '#description' => $this->t('Submission settings can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/engines/settings']),
      ],
    ];
  }

}
