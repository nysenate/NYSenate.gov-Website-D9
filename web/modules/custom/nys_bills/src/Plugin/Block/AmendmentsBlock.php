<?php

namespace Drupal\nys_bills\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block to generate NYS Bills Amendment Block.
 *
 * @Block(
 *   id = "nys_bills_amendment_block",
 *   admin_label = @Translation("NYS Bills Amendment Block"),
 *   category = @Translation("NYS Bills"),
 * )
 */
class AmendmentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Bills Helper.
   *
   * @var \Drupal\nys_bills\BillsHelper
   */
  protected $billsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->billsHelper = $container->get('nys_bill.bills_helper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = $this->routeMatch->getParameter('node');

    if (!empty($node) && $node->bundle() === 'bill') {

      // A few immediate references for the bill object.
      $bill_session_year = $node->field_ol_session->value;
      $print_no = $node->field_ol_base_print_no->value;
      $amended_versions_result = $this->billsHelper->getBillVersions($node);

      $amendments = $this->billsHelper->findsFeaturedLegislationQuote($amended_versions_result);

      foreach ($amendments as $key => $item) {
        $amendments[$key]['sponsors_array'] = $this->billsHelper->resolveAmendmentSponsors($item['node'], $node->field_ol_chamber->value);
      }

      // Check for values in the Same As field for opposite chamber versions.
      $same_as = json_decode($node->field_ol_same_as->value);

      // Check status values for the bill and potential committee.
      $last_status = $node->field_ol_last_status->value;
      $last_status_comm = $node->field_ol_latest_status_committee->value;

      $comm_status_pre = NULL;
      // Wrap Committee in a link if it is a Senate committee.
      if ($last_status === 'IN_SENATE_COMM') {
        $committee_bill_details = 'Senate ' . $last_status_comm;
        $target = Url::fromUserInput('/committees/' . Html::getClass($last_status_comm), []);
        $comm_status_pre = Link::fromTextAndUrl($committee_bill_details, $target)->toString();
      }
      if ($last_status === 'IN_ASSEMBLY_COMM') {
        $committee_bill_details = 'Assembly ' . $last_status_comm;
        $comm_status_pre = $committee_bill_details;
      }

      // Load all bills associated with this bill's taxonomy root.
      $related_metadata = [];

      $session_root_id = $node->field_bill_multi_session_root->target_id;
      $related_bills = $this->billsHelper->loadBillsFromTid($session_root_id);
      $metadata = $this->billsHelper->getBillMetadata($related_bills);

      // Load all bills associated with this bill's taxonomy root.
      $related_metadata = array_filter($metadata, function ($v) {
        return $v->print_num === $v->base_print_num;
      });

      // If a "same as" exists, add its previous versions to this bill as well.
      if (!empty($same_as)) {
        // Get metadata for all bills related to this one by taxonomy.
        // If "Same As" entries exist, get all previous versions of those bills.
        foreach ($same_as as &$billid) {
          $billid = (object) $billid;
          $bill_prev_versions = $this->billsHelper->getPrevVersions($billid->session, $billid->printNo);

          if (!empty($bill_prev_versions)) {
            $bill_prev_versions = reset($bill_prev_versions);
            $billid->nid = $bill_prev_versions;

            $bill = \Drupal::entityTypeManager()
              ->getStorage('node')->load($bill_prev_versions);
            if ($bill) {
              $billid->url = $bill->toUrl()->toString();
            }

          }
        }

        // Query the database for previous versions of opposite chamber bills.
        if (!empty($same_as[0]->nid)) {
          $opposite_chamber_versions = $this->billsHelper->getOppositeChamberPrevVersions($same_as[0]->nid);
          $related_metadata = array_merge($related_metadata, $opposite_chamber_versions);
        }
      }

      // Format display items for previous versions.
      // They need to be categorized
      // by legislative session.
      $previous_versions = [];
      foreach ($related_metadata as $key => $val) {
        if (!empty($val->session) && ($val->session === $bill_session_year)) {
          $t_sess = $this->billsHelper->standardizeSession((int) $val->session);
          $t_year = substr($t_sess, 0, 4);
          /* @phpstan-ignore-next-line */
          $t_pnum = strtoupper($val->base_print_num);
          $t_link = Url::fromUserInput('/legislation/bills/' . $t_year . '/' . $t_pnum);
          $previous_versions[$t_sess][$t_pnum] = Link::fromTextAndUrl($t_pnum, $t_link)->toString();
        }
      }

      // Now add the previous versions and same as to the template variables.
      $vars['same_as'] = $same_as;
      $prev_vers = [];
      ksort($previous_versions);
      foreach ($previous_versions as $index => $prev_leg) {
        $prev_vers[$index] = (implode(', ', $prev_leg));
      }

      // Add the prefix text for previous versions.
      $prev_vers_pre = '';
      if (count($prev_vers) > 1) {
        $prev_vers_pre = $this->t('Versions Introduced in Other Legislative Sessions:');
      }
      else {
        $current = current(array_keys($previous_versions));
        $prev_vers_pre = $this->t('Versions Introduced in @current Legislative Session:', ['@current' => $current]);
      }

      $build = [
        '#theme' => 'nys_bills__amendments_block',
        '#content' => [
          'amendments' => $amendments,
          'bill_wrapper' => $node,
          'base_print_no' => $node->field_ol_base_print_no->value,
          'session_year' => $bill_session_year,
          'amended_versions' => $amended_versions_result,
          'active_version' => $session_root_id,
          'comm_status_pre' => $comm_status_pre,
          'same_as' => $same_as,
          'prev_vers' => $prev_vers,
          'prev_vers_pre' => $prev_vers_pre,
          'ol_base_url' => \Drupal::state()->get('openleg_base_url', 'https://legislation.nysenate.gov'),
          'version' => '',
        ],
      ];
      return $build;
    }
    return [];
  }

}
