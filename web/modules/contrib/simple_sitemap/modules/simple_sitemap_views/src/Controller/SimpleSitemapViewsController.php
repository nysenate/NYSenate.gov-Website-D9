<?php

namespace Drupal\simple_sitemap_views\Controller;

use Drupal\simple_sitemap\Form\FormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_sitemap_views\SimpleSitemapViews;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for Simple XML Sitemap Views admin page.
 */
class SimpleSitemapViewsController extends ControllerBase {

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $sitemapViews;

  /**
   * SimpleSitemapViewsController constructor.
   *
   * @param \Drupal\simple_sitemap_views\SimpleSitemapViews $sitemap_views
   *   Views sitemap data.
   */
  public function __construct(SimpleSitemapViews $sitemap_views) {
    $this->sitemapViews = $sitemap_views;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.views')
    );
  }

  /**
   * Builds a listing of indexed views displays.
   *
   * @return array
   *   A render array.
   */
  public function content() {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('View'),
        $this->t('Display'),
        $this->t('Variants'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No view displays are set to be indexed yet. <a href="@url">Edit a view.</a>', ['@url' => $GLOBALS['base_url'] . '/admin/structure/views']),
    ];

    foreach ($this->sitemapViews->getIndexableViews() as $index => $view) {
      $table[$index]['view'] = ['#markup' => $view->storage->label()];
      $table[$index]['display'] = ['#markup' => $view->display_handler->display['display_title']];

      $variants = $this->sitemapViews->getIndexableVariants($view);
      $variants = implode(', ', array_keys($variants));
      $table[$index]['variants'] = ['#markup' => $variants];

      // Link to view display edit form.
      $display_edit_url = Url::fromRoute('entity.view.edit_display_form', [
        'view' => $view->id(),
        'display_id' => $view->current_display,
      ]);

      $table[$index]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'display_edit' => [
            'title' => $this->t('Edit'),
            'url' => $display_edit_url,
          ],
        ],
      ];
    }

    // Show information about indexed displays.
    $build['simple_sitemap_views'] = [
      '#prefix' => FormHelper::getDonationText(),
      '#title' => $this->t('Indexed view displays'),
      '#type' => 'fieldset',
      'table' => $table,
    ];

    return $build;
  }

}
