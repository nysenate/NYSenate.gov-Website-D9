<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Database\Connection;

/**
 * Class SimplesitemapSitemapsForm
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapSitemapsForm extends SimplesitemapFormBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * SimplesitemapSitemapsForm constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    Connection $database,
    DateFormatter $date_formatter
  ) {
    parent::__construct(
      $generator,
      $form_helper
    );
    $this->db = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_sitemaps_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_settings']['#prefix'] = FormHelper::getDonationText();
    $form['simple_sitemap_settings']['#attached']['library'][] = 'simple_sitemap/sitemaps';
    $queue_worker = $this->generator->getQueueWorker();

    $form['simple_sitemap_settings']['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sitemap status'),
      '#markup' => '<div class="description">' . $this->t('Sitemaps can be regenerated on demand here.') . '</div>',
      '#description' => $this->t('Variants can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/variants']),
    ];

    $form['simple_sitemap_settings']['status']['actions'] = [
      '#prefix' => '<div class="clearfix"><div class="form-item">',
      '#suffix' => '</div></div>',
    ];

    $form['simple_sitemap_settings']['status']['actions']['rebuild_queue_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild queue'),
      '#submit' => ['::rebuildQueue'],
      '#validate' => [],
    ];

    $form['simple_sitemap_settings']['status']['actions']['regenerate_submit'] = [
      '#type' => 'submit',
      '#value' => $queue_worker->generationInProgress()
        ? $this->t('Resume generation')
        : $this->t('Rebuild queue & generate'),
      '#submit' => ['::generateSitemap'],
      '#validate' => [],
    ];

    $form['simple_sitemap_settings']['status']['progress'] = [
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];

    $form['simple_sitemap_settings']['status']['progress']['title']['#markup'] = $this->t('Progress of sitemap regeneration');

    $total_count = $queue_worker->getInitialElementCount();
    if (!empty($total_count)) {
      $indexed_count = $queue_worker->getProcessedElementCount();
      $percent = round(100 * $indexed_count / $total_count);

      // With all results processed, there still may be some stashed results to be indexed.
      $percent = $percent === 100 && $queue_worker->generationInProgress() ? 99 : $percent;

      $index_progress = [
        '#theme' => 'progress_bar',
        '#percent' => $percent,
        '#message' => $this->t('@indexed out of @total queue items have been processed.<br>Each sitemap variant is published after all of its items have been processed.', ['@indexed' => $indexed_count, '@total' => $total_count]),
      ];
      $form['simple_sitemap_settings']['status']['progress']['bar']['#markup'] = render($index_progress);
    }
    else {
      $form['simple_sitemap_settings']['status']['progress']['bar']['#markup'] = '<div class="description">' . $this->t('There are no items to be indexed.') . '</div>';
    }

    $sitemap_manager = $this->generator->getSitemapManager();
    $sitemap_settings = [
      'base_url' => $this->generator->getSetting('base_url', ''),
      'default_variant' => $this->generator->getSetting('default_variant', NULL),
    ];
    $sitemap_statuses = $this->fetchSitemapInstanceInfo();
    $published_timestamps = $this->fetchSitemapInstancePublishedTimestamps();
    foreach ($sitemap_manager->getSitemapTypes() as $type_name => $type_definition) {
      if (!empty($variants = $sitemap_manager->getSitemapVariants($type_name, FALSE))) {
        $sitemap_generator = $sitemap_manager
          ->getSitemapGenerator($type_definition['sitemapGenerator'])
          ->setSettings($sitemap_settings);

        $form['simple_sitemap_settings']['status']['types'][$type_name] = [
          '#type' => 'details',
          '#title' => '<em>' . $type_definition['label'] . '</em> ' . $this->t('sitemaps'),
          '#open' => !empty($variants) && count($variants) <= 5,
          '#description' => !empty($type_definition['description']) ? '<div class="description">' . $type_definition['description'] . '</div>' : '',
        ];
        $form['simple_sitemap_settings']['status']['types'][$type_name]['table'] = [
          '#type' => 'table',
          '#header' => [$this->t('Variant'), $this->t('Status'), $this->t('Link count')],
          '#attributes' => ['class' => ['form-item', 'clearfix']],
        ];
        foreach ($variants as $variant_name => $variant_definition) {
          if (!isset($sitemap_statuses[$variant_name])) {
            $row['name']['data']['#markup'] = '<span title="' . $variant_name . '">' . $this->t($variant_definition['label']) . '</span>';
            $row['status'] = $this->t('pending');
            $row['count'] = '';
          }
          else {
            switch ($sitemap_statuses[$variant_name]['status']) {

              case 0:
                $row['name']['data']['#markup'] = '<span title="' . $variant_name . '">' . $this->t($variant_definition['label']) . '</span>';
                $row['status'] = $this->t('generating');
                $row['count'] = '';
                break;

              case 1:
              case 2:
                $row['name']['data']['#markup'] = $this->t('<a href="@url" target="_blank">@variant</a>',
                  ['@url' => $sitemap_generator->setSitemapVariant($variant_name)->getSitemapUrl(), '@variant' => $this->t($variant_definition['label'])]
                );
                $row['status'] = $this->t(($sitemap_statuses[$variant_name]['status'] === 1
                  ? 'published on @time'
                  : 'published on @time, regenerating'
                ), ['@time' => $this->dateFormatter->format($published_timestamps[$variant_name])]);
                // Once the sitemap has been regenerated after
                // simple_sitemap_update_8305() there will always be a link
                // count.
                $row['count'] = $sitemap_statuses[$variant_name]['link_count'] > 0
                  ? $sitemap_statuses[$variant_name]['link_count']
                  : $this->t('unavailable');
                break;
            }
          }
          $form['simple_sitemap_settings']['status']['types'][$type_name]['table']['#rows'][$variant_name] = isset($row) ? $row : [];
          unset($sitemap_statuses[$variant_name]);
        }
      }
    }
    if (empty($form['simple_sitemap_settings']['status']['types'])) {
      $form['simple_sitemap_settings']['status']['types']['#markup'] = $this->t('No variants have been defined');
    }

    return $form;
  }

  /**
   * @return array
   *  Array of sitemap statuses and link counts keyed by variant name.
   *  Status values:
   *  0: Instance is unpublished
   *  1: Instance is published
   *  2: Instance is published but is being regenerated
   *
   * @todo Implement SitemapGeneratorBase::isPublished() per sitemap instead or at least return a constant.
   */
  protected function fetchSitemapInstanceInfo() {
    $results = $this->db
      ->query('SELECT type, status, SUM(link_count) as link_count FROM {simple_sitemap} GROUP BY type, status ORDER BY type, status ASC')
      ->fetchAll();

    $instance_info = [];
    foreach ($results as $i => $result) {
      $instance_info[$result->type] = [
        'status' => isset($instance_info[$result->type]) ? $result->status + 1 : (int) $result->status,
        'link_count' => (int) $result->link_count,
      ];
    }

    return $instance_info;
  }

  /**
   * @return array
   *
   * @todo Implement SitemapGeneratorBase::getPublishedTimestamp() per sitemap instead or at least return a constant.
   */
  protected function fetchSitemapInstancePublishedTimestamps() {
    return $this->db
      ->query('SELECT type, MAX(sitemap_created) FROM (SELECT sitemap_created, type FROM {simple_sitemap} WHERE status = :status) AS timestamps GROUP BY type', [':status' => 1])
      ->fetchAllKeyed(0, 1);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generateSitemap(array &$form, FormStateInterface $form_state) {
    $this->generator->generateSitemap();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(array &$form, FormStateInterface $form_state) {
    $this->generator->rebuildQueue();
  }

}
