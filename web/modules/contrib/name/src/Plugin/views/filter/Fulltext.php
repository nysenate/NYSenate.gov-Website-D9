<?php

namespace Drupal\name\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by fulltext search.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("name_fulltext")
 */
class Fulltext extends FilterPluginBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new Fulltext object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * Supported operations.
   */
  protected function operators() {
    return [
      'contains' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'op_contains',
        'values' => 1,
      ],
      'word' => [
        'title' => $this->t('Contains any word'),
        'short' => $this->t('has word'),
        'method' => 'op_word',
        'values' => 1,
      ],
      'allwords' => [
        'title' => $this->t('Contains all words'),
        'short' => $this->t('has all'),
        'method' => 'op_word',
        'values' => 1,
      ],
    ];
  }

  /**
   * Build strings from the operators() for 'select' options.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => ['title' => $this->t('Enter the name you wish to search for.')],
      '#title' => $this->isExposed() ? '' : $this->t('Value'),
    ];
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    // Don't filter on empty strings.
    if (empty($this->value[0])) {
      return;
    }
    $field = "$this->tableAlias.$this->realField";
    $fulltext_field = "LOWER(CONCAT(' ', COALESCE({$field}_title, ''), ' ', COALESCE({$field}_given, ''), ' ', COALESCE({$field}_middle, ''), ' ', COALESCE({$field}_family, ''), ' ', COALESCE({$field}_generational, ''), ' ', COALESCE({$field}_credentials, '')))";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($fulltext_field);
    }
  }

  /**
   * Contains operation.
   *
   * @param string $fulltext_field
   *   The db field.
   */
  public function op_contains($fulltext_field) {
    $value = mb_strtolower($this->value[0]);
    $value = str_replace(' ', '%', $value);
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$fulltext_field LIKE $placeholder", [$placeholder => '% ' . $value . '%']);
  }

  /**
   * The word operation.
   *
   * @param string $fulltext_field
   *   The db field.
   */
  public function op_word($fulltext_field) {
    $where = $this->operator == 'word' ? new Condition('OR') : new Condition('AND');
    $value = mb_strtolower($this->value[0]);

    $words = preg_split('/ /', $value, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as $word) {
      $placeholder = $this->placeholder();
      $where->where("$fulltext_field LIKE $placeholder", [$placeholder => '% ' . $this->connection->escapeLike($word) . '%']);
    }

    $this->query->addWhere($this->options['group'], $where);
  }

}
