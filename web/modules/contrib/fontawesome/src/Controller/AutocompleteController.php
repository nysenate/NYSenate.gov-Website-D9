<?php

namespace Drupal\fontawesome\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\fontawesome\FontAwesomeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Drupal Font Awesome manager service.
   *
   * @var \Drupal\fontawesome\FontAwesomeManagerInterface
   */
  protected $fontAwesomeManager;

  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $fontAwesomeManager = $container->get('fontawesome.font_awesome_manager');
    $configFactory = $container->get('config.factory');
    return new static($fontAwesomeManager, $configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FontAwesomeManagerInterface $fontAwesomeManager, ConfigFactory $config_factory) {
    $this->fontAwesomeManager = $fontAwesomeManager;
    $this->configFactory = $config_factory;
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      // Load the icon data so we can check for a valid icon.
      $iconData = $this->fontAwesomeManager->getIconsWithCategories();

      // Load the configuration settings.
      $configuration_settings = $this->configFactory->get('fontawesome.settings');

      // Determine which files we are using.
      $activeFiles = [
        'use_solid_file' => is_null($configuration_settings->get('use_solid_file')) === TRUE ? TRUE : $configuration_settings->get('use_solid_file'),
        'use_regular_file' => is_null($configuration_settings->get('use_regular_file')) === TRUE ? TRUE : $configuration_settings->get('use_regular_file'),
        'use_light_file' => is_null($configuration_settings->get('use_light_file')) === TRUE ? TRUE : $configuration_settings->get('use_light_file'),
        'use_brands_file' => is_null($configuration_settings->get('use_brands_file')) === TRUE ? TRUE : $configuration_settings->get('use_brands_file'),
        'use_duotone_file' => is_null($configuration_settings->get('use_duotone_file')) === TRUE ? TRUE : $configuration_settings->get('use_duotone_file'),
        'use_thin_file' => is_null($configuration_settings->get('use_thin_file')) === TRUE ? TRUE : $configuration_settings->get('use_thin_file'),
      ];

      // Check each icon to see if it starts with the typed string.
      foreach ($iconData as $thisIcon) {
        // If the string is found.
        if (strpos($thisIcon['name'], $typed_string) === 0 || in_array($typed_string, $thisIcon['search_terms'])) {
          $iconRenders = [];
          // Loop over each style.
          foreach ($thisIcon['styles'] as $style) {

            // Determine the prefix.
            $iconPrefix = '';
            switch ($style) {

              case 'brands':
                // Don't show if unavailable.
                if (!$activeFiles['use_brands_file']) {
                  break;
                }
                $iconPrefix = 'fab';
                break;

              case 'light':
                // Don't show if unavailable.
                if (!$activeFiles['use_light_file']) {
                  break;
                }
                $iconPrefix = 'fal';
                break;

              case 'regular':
                // Don't show if unavailable.
                if (!$activeFiles['use_regular_file']) {
                  break;
                }
                $iconPrefix = 'far';
                break;

              case 'duotone':
                // Don't show if unavailable.
                if (!$activeFiles['use_duotone_file']) {
                  break;
                }
                $iconPrefix = 'fad';
                break;

              case 'thin':
                // Don't show if unavailable.
                if (!$activeFiles['use_thin_file']) {
                  break;
                }
                $iconPrefix = 'fat';
                break;

              case 'kit_uploads':
                $iconPrefix = 'fak';
                break;

              default:
              case 'solid':
                // Don't show if unavailable.
                if (!$activeFiles['use_solid_file']) {
                  break;
                }
                $iconPrefix = 'fas';
                break;
            }
            // Render the icon.
            if (!empty($iconPrefix)) {
              $iconRenders[] = new FormattableMarkup('<i class=":prefix fa-:icon fa-fw fa-2x"></i> ', [
                ':prefix' => $iconPrefix,
                ':icon' => $thisIcon['name'],
              ]);
            }
          }

          // Don't show if we have no available icons.
          if (count($iconRenders) == 0) {
            continue;
          }

          $results[] = [
            'value' => $thisIcon['name'],
            'label' => implode('', $iconRenders) . $thisIcon['name'],
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
