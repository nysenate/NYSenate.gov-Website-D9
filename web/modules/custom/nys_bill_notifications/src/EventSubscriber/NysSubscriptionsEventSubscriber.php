<?php

namespace Drupal\nys_bill_notifications\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\node\Entity\Node;
use Drupal\nys_sendgrid\Api\Template;
use Drupal\nys_sendgrid\TemplatesManager;
use Drupal\nys_slack\Service\Slack;
use Drupal\nys_subscriptions\Event\GetSubscribersEvent;
use Drupal\nys_subscriptions\Event\QueueItemReferences;
use Drupal\nys_subscriptions\Event\QueueItemSubscribers;
use Drupal\nys_subscriptions\Event\QueueItemTokens;
use Drupal\nys_subscriptions\Events;
use Drupal\nys_subscriptions\Exception\FailedTemplateAssignment;
use Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity;
use Drupal\nys_subscriptions\Subscriber;
use Drupal\nys_users\UsersHelper;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\To;
use SendGrid\Mail\TypeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * NYS Bill Notifications event subscriber.
 */
class NysSubscriptionsEventSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;

  /**
   * Local copy for nys_bill_notifications.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * NYS Slack messaging service.
   *
   * @var \Drupal\nys_slack\Service\Slack
   */
  protected Slack $slack;

  /**
   * Logging channel for nys_bill_notifications.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * Local copy of NYSenate global site settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $siteConfig;

  /**
   * Constructs event subscriber.
   */
  public function __construct(ConfigFactory $configFactory, Slack $slack, EntityTypeManagerInterface $manager) {
    $this->config = $configFactory->get('nys_bill_notifications.settings');
    $this->slack = $slack;
    $this->manager = $manager;
    $this->logger = $this->getLogger('nys_bill_notifications');

    // Also, set a reference to the global site config.
    $this->siteConfig = $configFactory->get('nys_config.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      Events::GET_SUBSCRIBERS => 'injectSubscribers',
      Events::QUEUEITEM_REFERENCES => 'injectReferences',
      Events::QUEUEITEM_TOKENS => 'populateItemTokens',
      Events::QUEUEITEM_SUBSCRIBERS => 'populateSubscriberTokens',
    ];
  }

  /**
   * Listens for event nys_subscriptions.get.subscribers.
   */
  public function injectSubscribers(GetSubscribersEvent $event) {
    // Just emails, not associated with an actual user.
    $new_subs = [$this->config->get('inject_email') ?? ''];

    // Add any user accounts to be injected.
    $users = User::loadMultiple([$this->config->get('inject_userid') ?? '']);
    foreach ($users as $user) {
      $new_subs[] = ['uid' => $user->id(), 'email' => $user->getEmail()];
    }

    // For each injection, add a subscriber based on this template.
    $new_inject = [
      'sub_type' => 'bill_notifications',
      'subscribe_to_type' => $event->entity->getEntityTypeId(),
      'subscribe_to_id' => $event->entity->id(),
    ];
    foreach ($new_subs as $inject) {
      if (is_array($inject)) {
        $new_inject['email'] = $inject['email'];
        $new_inject['uid'] = $inject['uid'];
      }
      else {
        $new_inject['email'] = $inject;
        $new_inject['uid'] = 0;
      }
      try {
        $event->subscribers[] = Subscriber::createFromValues($new_inject);
      }
      catch (\Throwable) {
        // This should never fire, and I'm willing to ignore it if it does.
      }
    }
  }

  /**
   * Listens for event nys_subscriptions.queueitem.references.
   */
  public function injectReferences(QueueItemReferences $event) {
    // Set the mail key and module for the queue item.
    $event->item->mailModule = 'nys_bill_notifications';
    $event->item->mailKey = strtolower(
          $event->item->data['primary_event']['name'] ?? 'bill_notifications_general'
      );
    $event->item->references['categories'][] = 'bill_notifications';

    // Try to load a reference to the updated bill.  If a bill cannot be loaded,
    // cancel processing for this queue item and report.
    $nid = $event->item->data['bill_nid'] ?? 0;
    try {
      $bill = $this->manager->getStorage('node')->load($nid);
    }
    catch (\Throwable) {
      $bill = NULL;
    }
    if (($bill instanceof Node) && ($bill->getType() == 'bill')) {
      $event->item->references['updated_bill'] = $bill;
    }
    else {
      $event->item->readyToSend = FALSE;
      $this->logger->warning('Failed to load bill for queue item @id', ['@id' => $event->item->item_id ?? '<no id>']);
    }
  }

  /**
   * Listens for event nys_subscriptions.queueitem.tokens.
   *
   * Adds template substitutions for data points common to all subscribers.
   * Will only do work for queue "bill_notifications".
   *
   * @throws \Drupal\nys_subscriptions\Exception\FailedTemplateAssignment
   *   If a template cannot be found based on the primary event.
   * @throws \Drupal\nys_subscriptions\Exception\InvalidSubscriptionEntity
   *   If a bill Node cannot be found based on source/target entity.
   */
  public function populateItemTokens(QueueItemTokens $event) {
    if ($event->item->queue->getName() == 'bill_notifications') {
      $item = $event->item;

      // Try to get a template ID for the primary event.  If not, report/fail.
      $primary_name = $item->data['primary_event']['name'] ?? '';

      if (!($template = $this->getTemplateByName($primary_name))) {
        $this->slack
          ->init()
          ->addAttachment("Event ID:\n" . ($item->data['item_id'] ?? '<no id>'))
          ->addAttachment("Bill ID:\n" . ($item->data['print_num'] ?? '<no print>'))
          ->addAttachment("Event:\n" . $primary_name)
          ->setTitle("Blank Template Detected")
          ->send("A queued event could not be matched to a SendGrid template.");
        throw new FailedTemplateAssignment('A queued event could not be matched to a template');
      }
      $item->substitutions['template_id'] = $template->getId();

      // SendGrid will set a subject via the template, but it is a good idea
      // to offer a fallback.  If configured, set it.
      if ($subject = $this->config->get('subject')) {
        $item->substitutions['subject'] = $subject;
      }

      // Ensure we have a bill Node.
      if (!($entity = Node::load($item->data['bill_nid'] ?? 0))) {
        throw new InvalidSubscriptionEntity('Item tokens could not find a source/target entity');
      }

      // An easy reference.
      $subs = &$item->substitutions['common'];

      // Populate the node field references for the 'node' section.
      $refs = [
        'session' => 'field_ol_session',
        'print_number' => 'field_ol_print_no',
        'base_print' => 'field_ol_base_print_no',
        'active_version' => 'field_ol_active_version',
        'chamber' => 'field_ol_chamber',
        'summary' => 'field_ol_name',
        'sponsor' => 'field_ol_sponsor_name',
        'latest_committee' => 'field_ol_latest_status_committee',
      ];
      foreach ($refs as $key => $val) {
        $sub_name = '%bill.' . $key . '%';
        $sub_value = $entity->{$val}->value ?? '';

        // Special manipulations for specific fields.
        switch ($key) {
          case 'chamber':
            $sub_value = ucwords($sub_value);
            break;

          case 'active_version':
            $sub_value = $sub_value ?: 'Original';
            break;
        }

        $subs[$sub_name] = $sub_value;
      }

      // Add the full session reference (2015-2016 vs 2015).
      if ($test_val = ($subs['%bill.session%'] ?? '')) {
        $subs['%bill.full_session%'] = $test_val % 2
                ? $test_val . '-' . ((++$test_val) % 100)
                : --$test_val . '-' . ((++$test_val) % 100);
      }

      // Set the alternate chamber.
      $subs['%bill.alternate_chamber%'] = match ($subs['%bill.chamber%'] ?? '') {
        'Senate' => "Assembly",
                'Assembly' => "Senate",
                default => '',
      };

      // Get the same_as variable we need for rendering.
      $same_as_array = json_decode($item->references["updated_bill"]->field_ol_same_as->value ?? '');
      $subs['%bill.same_as%'] = $same_as_array[0]->printNo ?? '';

      // Values for committee emails.
      $url_formatted_committee_string = strtolower(
            str_replace(
                [',', ' '],
                ['', '-'],
                $subs['%bill.latest_committee%'] ?? ''
            )
        );
      $subs['%bill.committee_path%'] =
              $url_formatted_committee_string
              ? '/committees/' . $url_formatted_committee_string . '/'
              : '';

      // Get most recent actions from subscribed entity.
      $actions = '';
      $all_events = json_decode($entity->field_ol_all_actions->value);
      $recent_events = array_slice($all_events->items, -3, 3);
      foreach ($recent_events as $val) {
        $actions .= '<li>' . date("M j, Y", strtotime($val->date)) .
                    ' - ' . $val->text . '</li>';
      }
      $subs['%bill.actions%'] = $actions;

      // Populate the "other events" HTML.
      $extra = '';
      foreach (($item->data['events'] ?? []) as $val) {
        $extra .= '<li>' . $val['text'] . '</li>';
      }
      $subs['%other_events%'] = $extra;

      // Add any context data points set in the primary event.  Context takes
      // precedence over anything set above.
      foreach (($item->data['primary_event']['context'] ?? []) as $key => $val) {
        $subs["%{$key}%"] = $val;
      }

      // Add the governor's name.
      $subs['%governor.full_name%'] = $this->siteConfig->get('governor_name');
    }

  }

  /**
   * Listens for event nys_subscriptions.queueitem.subscribers.
   *
   * Adds template substitutions for data points specific to an individual
   * subscriber.  Will be run once for each subscriber in a queue item.
   * Responds only to queue "bill_notifications".
   */
  public function populateSubscriberTokens(QueueItemSubscribers $event) {
    if ($event->item->queue->getName() == 'bill_notifications') {
      // Some references.
      $item = $event->item;
      $subscriber = $event->subscriber;

      // The target must be a bill node.  If not, report and skip.
      /**
       * @var \Drupal\node\Entity\Node $bill
       */
      $bill = $item->references['updated_bill'];
      if (!(($bill instanceof Node) && ($bill->bundle() == 'bill'))) {
        $this->logger->warning(
              "Subscriber @sub_id referenced an invalid target",
              [
                '@subscriber' => $subscriber,
                '@sub_id' => $subscriber->get('subId'),
                '@target' => get_class($bill),
                '@bundle' => $bill->bundle(),
              ]
          );
        return;
      }

      // If authenticated, set up the user, and get their name and email.  If
      // not, use the email from the subscriber.  Also, set the authentication
      // token and user key.
      if ($subscriber->get('uid')) {
        $user = User::load($subscriber->get('uid'));
        $auth = "%user.authenticated%";
        $um = $user->getEmail();
        $un = UsersHelper::getMailName($user);
        $user_key = $user->uuid() ?? '';
      }
      else {
        $user = NULL;
        $auth = "%user.unauthenticated%";
        $um = $un = $subscriber->get('email');
        $user_key = '';
      }

      // Attempt to create the recipient.  If this fails, skip the subscriber.
      try {
        $person = new Personalization();
        $person->addTo(new To($um, $un));
      }
      catch (\Throwable) {
        $this->logger->warning(
              "Could not create recipient for subscriber @sub_id (empty name/email?)",
              [
                '@subscriber' => $subscriber,
                '@email' => $um,
                '@name' => $un,
                '@sub_id' => $subscriber->get('subId'),
              ]
          );
        return;
      }

      // Set a reference to the district, if available.
      $district = $user->field_district->entity ?? NULL;

      // Compile all the tokens.  Start with the predefined 'common' entries.
      $tokens = $item->substitutions['common'] ?? [];

      // Process subscriber-level tokens.
      $tokens += [
        '%subscribe.date_long_form%' => $subscriber->formatCreated(),
        '%subscriber.account%' => $auth,
        '%subscriber.email%' => $subscriber->get('email') ?? '',
        '%subscriber.type%' => $subscriber->get('subType') ?? '',
        // @todo Remove %subscriber.why% from sendgrid templates.
        '%subscriber.why%' => "Subscription Sign-up",

        // Add keys for unsub/global unsub.
        '%subscriber.bill_unsub_token%' => $subscriber->get('uuid') ?? '',
        '%subscriber.global_token%' => $user_key,

        // Process tokens for the target entity (target of subscription).
        '%subscribe_node.session%' => $bill->field_ol_session->value ?? '',
        '%subscribe_node.print_number%' => $bill->field_ol_base_print_no->value ?? '',
        '%subscribe_node.chamber%' => $bill->field_ol_chamber->value ?? '',

        // Default senator tokens for unauthenticated subscribers.
        '%senator.has_senator%' => '0',
        '%senator.full_name%' => '',
        '%senator.page_url%' => '',
        '%senator.message_url%' => '',
        '%senator.district_number%' => '',
      ];

      // Add information about the user's senator, if applicable.
      if ($user && ($senator = UsersHelper::getSenator($user))) {
        try {
          $page_url = $senator->toUrl(options: ['absolute' => TRUE])
            ->toString();
        }
        catch (\Throwable) {
          $page_url = '';
        }
        $tokens['%senator.has_senator%'] = '1';
        $tokens['%senator.full_name%'] = $senator->field_full_name->value ?? '';
        if ($page_url) {
          $tokens['%senator.page_url%'] = $page_url;
          $tokens['%senator.message_url%'] = $page_url . '/contact';
        }
        if ($district) {
          $tokens['%senator.district_number%'] = $district->field_district_number->value ?? '';
        }
      }

      // Cycle through the tokens and add them as substitutions to the person.
      // Substitutions assert strict typing requirements on all keys and values.
      try {
        foreach ($tokens as $key => $val) {
          $person->addSubstitution($key, (string) $val);
        }
      }
      catch (TypeException $e) {
        $this->logger->error(
              "Skipping subscriber: failed to transcribe substitutions",
              ['@message' => $e->getMessage(), '@subscriber' => $subscriber]
          );
        return;
      }

      // Add the personalization to our item's collection.
      $item->substitutions['subscribers'][] = $person;
    }
  }

  /**
   * Finds a bill alert template by event name.
   *
   * @param string $name
   *   The event name.  This is generally an all-uppercase string.
   *
   * @return \Drupal\nys_sendgrid\Api\Template|null
   *   NULL if the template is not found.
   */
  protected function getTemplateByName(string $name): ?Template {
    $templates = TemplatesManager::getTemplates();
    $search = 'BILL_ALERT__' . $name;
    foreach ($templates as $ret) {
      /**
       * @var \Drupal\nys_sendgrid\Api\Template $ret
       */
      if ($search == ($ret->getName() ?? '')) {
        return $ret;
      }
    }
    return NULL;
  }

}
