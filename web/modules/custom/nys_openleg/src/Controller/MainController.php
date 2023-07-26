<?php

namespace Drupal\nys_openleg\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\nys_openleg\Service\ApiManager;
use Drupal\nys_openleg\StatuteHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MainController.
 *
 * Handles routing for nys_openleg module.
 */
class MainController extends ControllerBase {

  /**
   * The request context used to direct API and rendering behavior.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The app-level config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The book being requested.
   *
   * @var string
   */
  protected string $book;

  /**
   * The location being requested.
   *
   * @var string
   */
  protected string $location;

  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected PagerManagerInterface $pager;

  /**
   * The Openleg API Manager service.
   *
   * @var \Drupal\nys_openleg\Service\ApiManager
   */
  protected ApiManager $apiManager;

  /**
   * Constructor.
   *
   * Sets up the request and config objects, and configures the API
   * key to be used by ApiRequest objects.
   */
  public function __construct(RequestStack $request, ConfigFactory $config, FormBuilderInterface $formBuilder, PagerManagerInterface $pager, ApiManager $apiManager) {
    // Set the request context to the current request by default.
    $this->setRequest($request->getCurrentRequest());

    // Set the app config as a local reference.
    $this->config = $config->get('nys_openleg.settings');

    // Set the form builder.
    $this->formBuilder = $formBuilder;

    // Set the pager.
    $this->pager = $pager;

    // Set the API Manager service reference.
    $this->apiManager = $apiManager;
  }

  /**
   * Sets the request context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Injects the request and config objects into construction.
   *
   * @return static
   */
  public static function create(ContainerInterface $container): MainController {
    return new static(
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('pager.manager'),
      $container->get('manager.openleg_api')
    );
  }

  /**
   * Route handler for browsing statutes.  Returns a renderable array.
   *
   * Every request will conform to one of three conditions:
   *   - No book or location, or book == 'all'
   *     At the top level, requires rendering all book types.
   *   - A book name matching a law type identifier
   *     At the type level, requires rendering all books within that type.
   *   - Any other book name, and optional location
   *     At the book/location level, requires rendering that location.
   *
   * Note that book entries do have a location specified in OL.  If a
   * valid book is detected, but no location is provided, the location
   * for the book will be used instead.
   *
   * @return array
   *   A renderable array.
   */
  public function browse(string $book = 'all', string $location = ''): array {
    // Standardize the incoming request parts.
    $book = $book ?: 'all';

    // Fetch all the types, initialize the search.
    $law_types = StatuteHelper::getLawTypes();
    $suppress_search = FALSE;

    // Initialize important paths.
    $base_share_path = $this->request->getSchemeAndHttpHost() . StatuteHelper::baseUrl();
    $share_path = $base_share_path . '/' .
      implode('/', array_filter([$book, $location]));

    // Initialize the return.
    $ret = [
      '#theme' => 'nys_openleg_result',
      '#attached' => ['library' => ['nys_openleg/openleg']],
      '#share_path' => $share_path,
    ];

    // Initialize the breadcrumb sources.
    $law_type = '';
    $parents = NULL;

    // CONDITION ONE: all, or no book type.
    if ($book == 'all') {
      // Just need title and a list of book types.
      $ret['#title'] = 'The Laws of New York';
      $ret['#title_parts'] = [$ret['#title']];
      $ret['#list_items'] = $law_types;
    }
    // CONDITION TWO: a list of book types.
    elseif (array_key_exists($book, $law_types)) {
      // Minimal breadcrumb.  Also, title and a list of books.
      $law_type = $book;
      $ret['#title'] = StatuteHelper::LAW_TYPE_NAMES[$book] ?? '';
      $ret['#title_parts'] = [$ret['#title']];
      $ret['#list_items'] = array_map(
        function ($v) use ($base_share_path) {
          return [
            'name' => $v->lawId,
            'description' => $v->name,
            'url' => $base_share_path . '/' . $v->lawId,
          ];
        },
        StatuteHelper::getBooksByType($book)
      );
    }
    // CONDITION THREE: not either of the other two conditions.
    else {
      // Get the statute.  Consider any historical milestone being requested.
      $history = $this->request->request->get('history') ?: '';
      $statute = $this->apiManager
        ->getStatuteFull($book, $location, $history);

      // If the entry is not found (or other OL error), render the error page.
      if (!($statute->tree->success())) {
        return [
          '#theme' => 'nys_openleg_not_found',
          '#attached' => ['library' => ['nys_openleg/openleg']],
          '#browse_url' => $share_path,
        ];
      }

      // Set up the breadcrumb sources.
      $law_type = $statute->tree->result()->info->lawType;
      $parents = $statute->parents();

      // Set up some template variables.
      $ret['#title_parts'] = $statute->fullTitle();
      $ret['#title'] = implode(' ', $ret['#title_parts']);
      $ret['#entry_text'] = $statute->text();

      // Set the navigation references.
      $ret['#nav'] = array_map(
        function ($v) use ($base_share_path) {
          return $v ? [
            'name' => $v->docType . ' ' . $v->docLevelId,
            'description' => $v->title,
            'url' => $base_share_path . '/' . $v->lawId . '/' . $v->locationId,
          ] : [];
        },
        $statute->siblings() + ['up' => end($parents)]
      );

      // Include the milestone selection form.
      // @phpstan-ignore-next-line
      $ret['#history'] = $this->formBuilder
        ->getForm('Drupal\nys_openleg\Form\HistoryForm', $statute);

      // Generate the list_items from the statute children.
      $ret['#list_items'] = array_map(
        function ($v) use ($base_share_path) {
          return [
            'name' => $v->docType . ' ' . $v->docLevelId,
            'description' => $v->title,
            'url' => $base_share_path . '/' . $v->lawId . '/' . $v->locationId,
          ];
        },
        $statute->children()
      );
    }

    // Set up the email sharing variables.
    $ret['#mail_title'] = (is_array($ret['#title']))
      ? reset($ret['#title'])
      : $ret['#title'];
    $ret['#mail_link'] = "mailto:?subject=" . $ret['#mail_title'] .
      " | NY State Senate&body=Check out this law: " . $ret['#share_path'];

    // Get the breadcrumbs.
    $ret['#breadcrumbs'] = StatuteHelper::breadcrumbs($law_type, $parents);

    // Only render the search box if it has not been suppressed.
    if (!$suppress_search) {
      $ret['#search'] = $this->formBuilder
        ->getForm('Drupal\nys_openleg\Form\SearchForm');
    }

    return $ret;
  }

  /**
   * Route handler for searching statutes.  Returns a renderable array.
   */
  public function search($search_term = ''): array|Response {
    // If search_term is not in the URL, try to find it in form POST or the
    // query string.
    if (!$search_term) {
      $search_term = $this->request->request->get('search_term')
        ?? ($this->request->query->get('search_term') ?? '');

      // If one has been found, redirect to ensure the URL is proper.
      if ($search_term) {
        return $this->redirect('nys_openleg.search', ['search_term' => $search_term]);
      }
    }

    // Get the search form.
    // @phpstan-ignore-next-line
    $form = $this->formBuilder
      ->getForm('Drupal\nys_openleg\Form\SearchForm', $search_term);

    // Initialize the pager values.  Pager is zero-based, search is not.
    $use_pager = FALSE;
    $page = $this->pager->findPage() + 1;
    $per_page = (int) $this->request->query->get('per_page', 10);
    $results = [];
    $total = 0;

    // Execute the search and reformat into the results array.
    if ($search_term) {
      /**
       * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch $search
       */
      $search = $this->apiManager->getSearch(
        'statute',
        $search_term,
        [
          'page' => $page,
          'per_page' => $per_page,
        ]
      );
      $total = $search->total();
      foreach ($search->items() as $item) {
        // Find the important data points.
        $lawId = $item->result->lawId ?? '';
        $docType = $item->result->docType ?? '';
        $docLevelId = $item->result->docLevelId ?? '';
        $locationId = $item->result->locationId ?? '';
        $title = current($item->highlights->title ?? []) ?: ($item->result->title ?? '');

        // To ensure presentation, these four data points must be populated.
        // Location could be empty.
        if ($lawId && $docType && $docLevelId && $title) {
          // Create the data structure for the template.
          $url = StatuteHelper::baseUrl() . '/' .
            implode('/', array_filter([$lawId, $locationId]));
          $results[] = [
            'name' => implode(' ', [$lawId, $docType, $docLevelId]),
            'title' => $title,
            'snippets' => $item->highlights->text ?? [],
            'url' => $url,
          ];
        }
      }

      // Only use the pager theme if the results make sense.
      $offsets = $search->offset();
      if ($use_pager = ($search->count() > 0) && ($offsets['start'] < $offsets['end'])) {
        $this->pager->createPager($offsets['total'], $per_page);
      }
    }

    return [
      '#theme' => 'nys_openleg_search_results',
      '#attached' => ['library' => ['nys_openleg/openleg']],
      '#search_form' => $form,
      '#results' => $results,
      '#pager' => $use_pager ? ['#type' => 'pager'] : '',
      '#total' => $total,
      '#term' => $search_term,
    ];
  }

}
