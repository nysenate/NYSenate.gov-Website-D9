<?php

namespace Drupal\nys_legislation_explorer\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_legislation_explorer\SearchAdvancedLegislationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for nys_dashboard routes.
 */
class SearchLegislationController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Search Advanced Legislation helper service.
   *
   * @var \Drupal\nys_legislation_explorer\SearchAdvancedLegislationHelper
   */
  protected SearchAdvancedLegislationHelper $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->formBuilder = $container->get('form_builder');
    $instance->helper = $container->get('nys_legislation_explorer.helper');
    return $instance;
  }

  /**
   * Response for the bills page.
   */
  public function page() {
    $results_page = $this->helper->isResultsPage();
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($results_page) {
      try {
        $view = Views::getView('advanced_legislation_search');
        switch ($request->query->get('type')) {
          case 'bill':
            $content['search_legislation_results'] = $view->buildRenderable('search_results_block_bills');
            break;

          case 'session':
            $content['search_legislation_results'] = $view->buildRenderable('search_results_block_sessions');
            break;

          case 'resolution':
            $content['search_legislation_results'] = $view->buildRenderable('search_results_block_resolutions');
            break;

          case 'meeting':
            $content['search_legislation_results'] = $view->buildRenderable('search_results_block_meetings');
            break;

          case 'transcript':
            $content['search_legislation_results'] = $view->buildRenderable('search_results_block_transcripts');
            break;
        }
      }
      catch (\Exception $e) {
        $message = 'An unexpected error has occurred while searching. Please try again later.';
        $variables = [
          'msg' => $e->getMessage(),
          'search' => $request,
        ];
        \Drupal::service('logger.channel.nys_legislation_explorer')->error($message, $variables);
      }
    }
    $content['search_legislation_form'] = $this->formBuilder->getForm('Drupal\nys_legislation_explorer\Form\SearchAdvancedLegislationForm');
    return $content;
  }

}
