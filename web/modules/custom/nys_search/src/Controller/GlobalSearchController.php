<?php

namespace Drupal\nys_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\nys_search\GlobalSearchAdvancedHelper;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for nys_dashboard routes.
 */
class GlobalSearchController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Search Advanced Legislation helper service.
   *
   * @var \Drupal\nys_search\GlobalSearchAdvancedHelper
   */
  protected GlobalSearchAdvancedHelper $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->formBuilder = $container->get('form_builder');
    $instance->helper = $container->get('nys_search.helper');
    return $instance;
  }

  /**
   * Response for the bills page.
   */
  public function page() {
    $content['#cache']['contexts'][] = 'url.query_args';

    $content['global_search_form'] = $this->formBuilder->getForm('Drupal\nys_search\Form\GlobalSearchAdvancedForm');
    $request = \Drupal::service('request_stack')->getCurrentRequest();

    $results_page = $this->helper->isResultsPage();
    if ($results_page) {
      try {
        $view = Views::getView('core_search');
        $content['search_results'] = $view->buildRenderable('search_results_block');
      }
      catch (\Exception $e) {
        $message = 'An unexpected error has occurred while searching. Please try again later.';
        $variables = [
          'msg' => $e->getMessage(),
          'search' => $request,
        ];
        \Drupal::service('logger.channel.nys_search')->error($message, $variables);
      }
    }
    else {
      // Redirect to Homepage.
      $url = Url::fromUserInput('/')->toString();
      $response = new RedirectResponse($url);
      $response->send();
    }

    return $content;
  }

}
