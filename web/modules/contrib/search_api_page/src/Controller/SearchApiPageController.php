<?php

namespace Drupal\search_api_page\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_page\Entity\SearchApiPage;
use Drupal\search_api_page\SearchApiPageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Defines a controller to serve search pages.
 */
class SearchApiPageController extends ControllerBase {

  /**
   * The parse mode plugin manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  protected $parseModePluginManager;

  /**
   * The parse mode pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * SearchApiPageController constructor.
   *
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parseModePluginManager
   *   The parse mode plugin manager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The parse mode pager manager.
   */
  public function __construct(ParseModePluginManager $parseModePluginManager, PagerManagerInterface $pagerManager) {
    $this->parseModePluginManager = $parseModePluginManager;
    $this->pagerManager = $pagerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search_api.parse_mode'),
      $container->get('pager.manager')
    );
  }

  /**
   * Page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $search_api_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return array
   *   The page render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function page(Request $request, $search_api_page_name, $keys = '') {
    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    $search_api_page = $this->entityTypeManager()
      ->getStorage('search_api_page')
      ->load($search_api_page_name);

    // Keys can be in the query.
    if (!$search_api_page->getCleanUrl()) {
      $keys = $request->get('keys');
    }

    $build['#theme'] = 'search_api_page';
    if ($search_api_page->showSearchForm()) {
      $build = $this->addSearchForm($build, $search_api_page, $keys);
    }

    if (empty($keys) && !$search_api_page->showAllResultsWhenNoSearchIsPerformed()) {
      return $this->finishBuild($build, $search_api_page);
    }

    $query = $this->prepareQuery($request, $search_api_page);
    if (!empty($keys)) {
      $query->keys($keys);
    }

    $items = [];
    try {
      $result = $query->execute();
      /** @var \Drupal\search_api\Item\ItemInterface[] $items */
      $items = $result->getResultItems();
    }
    catch (\Exception $e) {
      if (error_displayable()) {
        $this->messenger()->addError($e->getMessage());
      }
      $this->getLogger('search_api_page')->error($e->getMessage());
    }
    $results = [];
    foreach ($items as $item) {
      $rendered = $this->createItemRenderArray($item, $search_api_page);
      if ($rendered === []) {
        continue;
      }
      $results[] = $rendered;
    }

    if (empty($results)) {
      return $this->finishBuildWithoutResults($build, $result, $search_api_page);
    }

    return $this->finishBuildWithResults($build, $result, $results, $search_api_page);
  }

  /**
   * Adds the search form to the build.
   *
   * @param array $build
   *   The build to add the form to.
   * @param \Drupal\search_api_page\SearchApiPageInterface $search_api_page
   *   The search api page.
   * @param mixed $keys
   *   The search word.
   *
   * @return array
   *   The build with the search form added to it.
   */
  protected function addSearchForm(array $build, SearchApiPageInterface $search_api_page, $keys) {
    $block_form = \Drupal::getContainer()->get('block_form.search_api_page');
    $block_form->setPageId($search_api_page->id());
    $args = [
      'keys' => $keys,
    ];
    $build['#form'] = $this->formBuilder()->getForm($block_form, $args);
    return $build;
  }

  /**
   * Creates a render array for the given result item.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to render.
   * @param \Drupal\search_api_page\SearchApiPageInterface $search_api_page
   *   The search api page.
   *
   * @return array
   *   A render array for the given result item.
   */
  protected function createItemRenderArray(ItemInterface $item, SearchApiPageInterface $search_api_page) {
    try {
      $originalObject = $item->getOriginalObject();
      if ($originalObject === NULL) {
        return [];
      }
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $originalObject->getValue();
    }
    catch (SearchApiException $e) {
      return [];
    }

    if (!$entity) {
      return [];
    }

    $viewedResult = [];
    if ($search_api_page->renderAsViewModes()) {
      $datasource_id = 'entity:' . $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $viewMode = $search_api_page->getViewModeConfig()
        ->getViewMode($datasource_id, $bundle);
      $viewedResult = $this->entityTypeManager()
        ->getViewBuilder($entity->getEntityTypeId())
        ->view($entity, $viewMode);
      $viewedResult['#search_api_excerpt'] = $item->getExcerpt();
    }

    if ($search_api_page->renderAsSnippets()) {
      $viewedResult = [
        '#theme' => 'search_api_page_result',
        '#item' => $item,
        '#entity' => $entity,
      ];
    }

    $metadata = CacheableMetadata::createFromRenderArray($viewedResult);
    $metadata->addCacheContexts(['url.path']);
    $metadata->addCacheTags(['search_api_page.style']);
    $metadata->applyTo($viewedResult);
    return $viewedResult;
  }

  /**
   * Finishes the build.
   *
   * @param array $build
   *   An array containing all page elements.
   * @param \Drupal\search_api_page\SearchApiPageInterface $searchApiPage
   *   The Search API page entity.
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   Search API result.
   *
   * @return array
   *   An array containing all page elements.
   */
  protected function finishBuild(array $build, SearchApiPageInterface $searchApiPage, ResultSetInterface $result = NULL) {
    $this->moduleHandler()->alter('search_api_page', $build, $result, $searchApiPage);

    $build['#cache'] = [
      'contexts' => [
        'url',
      ],
      'tags' => [
        'search_api_list:' . $searchApiPage->getIndex(),
      ],
    ];
    return $build;
  }

  /**
   * Prepares the search query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\search_api_page\SearchApiPageInterface $search_api_page
   *   The search api page.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The prepared query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function prepareQuery(Request $request, SearchApiPageInterface $search_api_page) {
    /** @var \Drupal\search_api\IndexInterface $search_api_index */
    $search_api_index = $this->entityTypeManager()
      ->getStorage('search_api_index')
      ->load($search_api_page->getIndex());
    $query = $search_api_index->query([
      'limit' => $search_api_page->getLimit(),
      'offset' => $request->get('page') !== NULL ? $request->get('page') * $search_api_page->getLimit() : 0,
    ]);
    $query->setSearchID('search_api_page:' . $search_api_page->id());

    /** @var \Drupal\search_api\ParseMode\ParseModeInterface $parse_mode */
    $parse_mode = $this->parseModePluginManager->createInstance($search_api_page->getParseMode());
    $query->setParseMode($parse_mode);

    // Add filter for current language.
    $langcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $query->setLanguages([
      $langcode,
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);

    $query->setFulltextFields($search_api_page->getSearchedFields());

    return $query;
  }

  /**
   * Adds the no results text and then finishes the build.
   *
   * @param array $build
   *   The build to finish.
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   Search API result.
   * @param \Drupal\search_api_page\SearchApiPageInterface $searchApiPage
   *   The Search API page entity.
   *
   * @return array
   *   The finished build render array.
   */
  protected function finishBuildWithoutResults(array $build, ResultSetInterface $result, SearchApiPageInterface $searchApiPage) {
    $build['#no_results_found'] = [
      '#markup' => $this->t('Your search yielded no results.'),
    ];

    $build['#search_help'] = [
      '#markup' => $this->t('<ul>
<li>Check if your spelling is correct.</li>
<li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
<li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
</ul>'),
    ];
    return $this->finishBuild($build, $searchApiPage, $result);
  }

  /**
   * Adds the results to the given build and then finishes it.
   *
   * @param array $build
   *   The build.
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   Search API result.
   * @param array $results
   *   The result item render arrays.
   * @param \Drupal\search_api_page\SearchApiPageInterface $search_api_page
   *   The search api page.
   *
   * @return array
   *   The finished build.
   */
  protected function finishBuildWithResults(array $build, ResultSetInterface $result, array $results, SearchApiPageInterface $search_api_page) {
    $build['#search_title'] = [
      '#markup' => $this->t('Search results'),
    ];

    $build['#no_of_results'] = [
      '#markup' => $this->formatPlural($result->getResultCount(), '1 result found', '@count results found'),
    ];

    $build['#results'] = $results;

    $this->pagerManager->createPager($result->getResultCount(), $search_api_page->getLimit());
    $build['#pager'] = [
      '#type' => 'pager',
    ];

    return $this->finishBuild($build, $search_api_page, $result);
  }

  /**
   * Title callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $search_api_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return string
   *   The page title.
   */
  public function title(Request $request, $search_api_page_name = NULL, $keys = '') {
    // Provide a default title if no search API page name is provided.
    if ($search_api_page_name === NULL) {
      return $this->t('Search');
    }

    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    $search_api_page = SearchApiPage::load($search_api_page_name);
    return $search_api_page->label();
  }

}
