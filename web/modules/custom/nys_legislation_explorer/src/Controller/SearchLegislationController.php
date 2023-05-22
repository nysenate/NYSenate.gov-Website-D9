<?php

namespace Drupal\nys_legislation_explorer\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * Response for the bills page.
   */
  public function page() {
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($request->query->get('type') ||
      $request->query->get('session_year') ||
      $request->query->get('issue') ||
      $request->query->get('printno') ||
      $request->query->get('status') ||
      $request->query->get('sponsor') ||
      $request->query->get('full_text') ||
      $request->query->get('committee') ||
      $request->query->get('transcript_type') ||
      $request->query->get('meeting_date') ||
      $request->query->get('publish_date_month') ||
      $request->query->get('publish_date_year') ||
      $request->query->get('date')) {
      try {
        $view = Views::getView('advanced_legislation_search');
        $content['search_legislation_results'] = $view->buildRenderable('search_results_block');
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
