<?php

namespace Drupal\views_ical\Plugin\views\style;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Url;
use Drupal\views_ical\ViewsIcalHelperInterface;
use Eluceo\iCal\Component\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Timezone;


/**
 * Style plugin to render an iCal feed. This provides a style usable for Feed displays.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "ical_wizard",
 *   title = @Translation("iCal Style Wizard"),
 *   help = @Translation("Display the results as an iCal feed using a UI to prompt hat fields to use."),
 *   theme = "views_view_icalwizard",
 *   display_types = {"feed"}
 * )
 */
class IcalWizard extends StylePluginBase {

  protected $usesFields = TRUE;
  protected $usesGrouping = FALSE;
  protected $usesRowPlugin = TRUE;

  /**
   * The iCal calendar.
   *
   * @var Eluceo\iCal\Component\Calendar
   */
  protected $calendar;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The helper service.
   *
   * @var \Drupal\views_ical\ViewsIcalHelperInterface
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, ViewsIcalHelperInterface $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->helper = $helper;
    $this->helper->setView($this->view);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('views_ical.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['date_field'] = ['default' => NULL];
    $options['summary_field'] = ['default' => NULL];
    $options['location_field'] = ['default' => 'none'];
    $options['url_field'] = ['default' => 'none'];
    $options['description_field'] = ['default' => 'none'];
    $options['no_time_field'] = ['default' => 'none'];
    $options['uid_field'] = ['default' => 'none'];
    $options['default_transparency'] = ['default' => 'transparent'];
    $options['use_vtimezone'] = ['default' => true];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    /** @var array $field_options */
    $field_options = $this->displayHandler->getFieldLabels();

    $field_options += ['none' => new TranslatableMarkup('None')];


    $form['instructions'] = [
      '#type' => 'markup',
      '#markup' => 'Use fields added from the fields section to map to iCal object properties.',
    ];

    $form['date_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Date field'),
      '#options' => $field_options,
      '#default_value' => $this->options['date_field'],
      '#description' => $this->t('Please identify the field to use as the iCal date for each item in this view.'),
      '#required' => TRUE,
    );

    $form['end_date_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('End date field'),
      '#options' => $field_options,
      '#default_value' => $this->options['end_date_field'],
      '#description' => $this->t('If the date field selected above is not a date rang, and if end dates are defined in a separate date field, then select that field here here.'),
    );

    $form['no_time_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('All day field'),
      '#options' => $field_options, //TODO: Filter out only boolean fields. Allow this to be empty.
      '#default_value' => $this->options['no_time_field'],
      '#description' => $this->t('Please identify the field to use to indicate an event will be all-day. If the date field uses the "Date all day" module, this option does not need to be set, and will be pulled automatically from the date field. TODO: Implement this.'),
    );

    $form['use_vtimezone'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use VTIMEZONE'),
      '#default_value' => $this->options['use_vtimezone'] ?? true,
      '#description' => $this->t('Use a VTIMEZONE entry. Enabling this may fix any issues with times not showing correctly for daylight savings. This was added relatively recently in the module, even though it is a part of the <a href="https://www.rfc-editor.org/rfc/rfc5545#section-3.6.5" target="_blank" rel="noopener noreferrer">iCal spec</a> so it can be toggled off if it breaks any installations here. VTIMEZONE objects are important for any dates showing as recurring, which cross daylight savings boundries. Future recurring events may not show up as the correct time. Also Outlook desktop client calendars have shown issues with single events not showing the correct time without these, regardless of recurring status.'),
    );


    $form['summary_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('SUMMARY field'),
      '#options' => $field_options,
      '#default_value' => $this->options['summary_field'],
      '#description' => $this->t('You may optionally change the SUMMARY component for each event in the iCal output. Choose which text field you would like to be output as the SUMMARY.'),
    );

    $form['location_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('LOCATION field'),
      '#options' => $field_options,
      '#default_value' => $this->options['location_field'],
      '#description' => $this->t('You may optionally include a LOCATION component for each event in the iCal output. Choose which text field you would like to be output as the LOCATION.'),
    );

    $form['url_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('URL field'),
      '#options' => $field_options,
      '#default_value' => $this->options['url_field'],
      '#description' => $this->t('You may optionally include a URL component for each event in the iCal output. Choose which link field you would like to be output as the URL.'),
    );

    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('DESCRIPTION field'),
      '#options' => $field_options,
      '#default_value' => $this->options['description_field'],
      '#description' => $this->t('You may optionally include a DESCRIPTION component for each event in the iCal output. Choose which text field you would like to be output as the DESCRIPTION.'),
    );

    $form['uid_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('UID field'),
      '#options' => $field_options, //TODO: Filter out only boolean fields.
      '#default_value' => $this->options['uid_field'],
      '#description' => $this->t('The field to use to generate a unique identifier for this calendar object. This is important for mapping to created events in client applications. Note, at this time, this is the only field that supports rewriting.'),
    );

    $form['default_transparency'] = [
      '#type' => 'select',
      '#title' => 'Default transparecy',
      '#options' => ['TRANSPARENT' => $this->t('Transparent'), 'OPAQUE' => $this->t('Opaque')],
      '#default value' => $this->options['uid_field'],
      '#description' => $this->t('Set the transparency setting for this field. Transparency indicates whether an event on a calendar occupies time or not. A transparent event\'s time is available for other free time searching apps to locate. An opaque event will indicate the time is not available for other applications to use.'),
    ];


  }

  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    $this->view->feedIcons[] = [];

    // Attach a link to the iCal feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'application/calendar',
      'href' => $url,
      'title' => $title,
    ];
  }


  /**
   * @return Eluceo\iCal\Component\Calendar
   */
  public function getCalendar(){
    return $this->calendar;
  }

  public function getHelper() {
    return $this->helper;
  }


  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      trigger_error('Drupal\views_ical\Plugin\views\style\Ical: Missing row plugin', E_WARNING);
      return [];
    }

    $this->helper->setView($this->view);

    // '-//Drupal iCal API//EN' becomes the PRODID
    $calendar = new Calendar('-//Drupal iCal API//EN');

    $this->calendar = $calendar;

    $parent_render = parent::render();
    
    if (isset($this->vTimezone)) {
      $this->calendar->setTimezone($this->vTimezone);
    }

    // Sets the 'X-WR-CALNAME" property. Just use the View name here.
    if ($this->view->getTitle()) {
      $calendar->setName($this->view->getTitle());
    }

    // Set correct mimetype.
    $this->view->getResponse()->headers->set('Content-Type', 'text/calendar; charset=utf-8');

    $build =  [
//      '#markup' => $render,
      '#markup' => $calendar->render(),
      //'#theme' => $this->themeFunctions(),
      //'#view' => $this->view,
      //'#options' => $this->options,
      //'#rows' => $parent_render,
    ];

    unset($this->view->row_index); //What is this doing?
    return $build;
  }


  public function getEntityFieldManager() {
    return $this->entityFieldManager;
  }

}
