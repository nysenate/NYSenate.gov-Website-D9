<?php

namespace Drupal\linkit\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\linkit\SubstitutionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Linkit filter.
 *
 * @Filter(
 *   id = "linkit",
 *   title = @Translation("Linkit URL converter"),
 *   description = @Translation("Updates links inserted by Linkit to point to entity URL aliases."),
 *   settings = {
 *     "title" = TRUE,
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class LinkitFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * Constructs a LinkitFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\linkit\SubstitutionManagerInterface $substitution_manager
   *   The substitution manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, SubstitutionManagerInterface $substitution_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->substitutionManager = $substitution_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('plugin.manager.linkit.substitution')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') !== FALSE && strpos($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//a[@data-entity-type and @data-entity-uuid]') as $element) {
        /** @var \DOMElement $element */
        try {
          // Load the appropriate translation of the linked entity.
          $entity_type = $element->getAttribute('data-entity-type');
          $uuid = $element->getAttribute('data-entity-uuid');

          // Skip empty attributes to prevent loading of non-existing
          // content type.
          if ($entity_type === '' || $uuid === '') {
            continue;
          }

          // Make the substitution optional, for backwards compatibility,
          // maintaining the previous hard-coded direct file link assumptions,
          // for content created before the substitution feature.
          if (!$substitution_type = $element->getAttribute('data-entity-substitution')) {
            $substitution_type = $entity_type === 'file' ? 'file' : SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;
          }

          $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
          if ($entity) {

            $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);

            /** @var \Drupal\Core\Url $url */
            $url = $this->substitutionManager
              ->createInstance($substitution_type)
              ->getUrl($entity);

            if (!$url) {
              continue;
            }

            // Parse link href as url, extract query and fragment from it.
            $href_url = parse_url($element->getAttribute('href'));
            // Check object type of $url for backwards compatibility (#3354873)
            // This check can be removed in the next major version of Linkit.
            $implementing_class = get_class($url);
            if ($implementing_class === 'Drupal\Core\GeneratedUrl') {
              @trigger_error('Drupal\Core\GeneratedUrl in Linkit Substitution plugins is deprecated in linkit:6.0.1 and must return Drupal\Core\Url in linkit:7.0.0. See https://www.drupal.org/project/linkit/issues/3354873', E_USER_DEPRECATED);
              $anchor = empty($href_url["fragment"]) ? '' : '#' . $href_url["fragment"];
              $query = empty($href_url["query"]) ? '' : '?' . $href_url["query"];
              /** @var \Drupal\Core\GeneratedUrl $url */
              $element->setAttribute('href', $url->getGeneratedUrl() . $query . $anchor);
            }
            else {
              if (!empty($href_url["fragment"])) {
                $url->setOption('fragment', $href_url["fragment"]);
              }
              if (!empty($href_url["query"])) {
                $parsed_query = [];
                parse_str($href_url['query'], $parsed_query);
                if (!empty($parsed_query)) {
                  $url->setOption('query', $parsed_query);
                }
              }
              $element->setAttribute('href', $url->toString());
            }

            // Set the appropriate title attribute.
            if ($this->settings['title'] && !$element->getAttribute('title')) {
              $access = $entity->access('view', NULL, TRUE);
              if (!$access->isForbidden()) {
                $element->setAttribute('title', $entity->label());
              }
              // Cache the linked entity access for the current user.
              $result->addCacheableDependency($access);
            }
            // Add cache awareness depending on substitution type.
            if ($implementing_class === 'Drupal\Core\GeneratedUrl') {
              // Add cache dependency if substitution uses legacy GeneratedUrl
              // This can be removed in 7.0.0 per
              // https://www.drupal.org/project/linkit/issues/3354873 .
              $result->addCacheableDependency($url);
            }
            // The linked entity (whose URL and title may change).
            $result->addCacheableDependency($entity);
          }
        }
        catch (\Exception $e) {
          watchdog_exception('linkit_filter', $e);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically set the <code>title</code> attribute to that of the (translated) referenced content'),
      '#default_value' => $this->settings['title'],
      '#attached' => [
        'library' => ['linkit/linkit.filter_html.admin'],
      ],
    ];
    return $form;
  }

}
