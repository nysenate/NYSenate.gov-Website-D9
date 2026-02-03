<?php

namespace Drupal\nys_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->formBuilder = $container->get('form_builder');
    $instance->requestStack = $container->get('request_stack');
    $instance->loggerFactory = $container->get('logger.factory');
    return $instance;
  }

  /**
   * Response for the search page.
   */
  public function page() {
    $content['#cache']['contexts'][] = 'url.query_args';

    $content['global_search_form'] = $this->formBuilder->getForm('Drupal\nys_search\Form\GlobalSearchAdvancedForm');

    try {
      $view = Views::getView('core_search');
      $content['search_results'] = $view->buildRenderable('search_results_block');
    }
    catch (\Exception $e) {
      $message = 'An unexpected error has occurred while searching. Please try again later.';
      $variables = [
        'msg' => $e->getMessage(),
        'search' => $this->requestStack->getCurrentRequest(),
      ];
      $this->loggerFactory->get('nys_search')->error($message, $variables);
    }

    return $content;
  }

}
