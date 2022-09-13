<?php

namespace Drupal\webform_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Event\ImportAwareInterface;
use Drupal\migrate\Event\RollbackAwareInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\webform\Entity\Webform;
use Drupal\node\Entity\Node;

/**
 * Drupal 6 webform source from database.
 *
 * @MigrateSource(
 *   id = "d6_webform",
 *   core = {6},
 *   source_module = "webform",
 *   destination_module = "webform"
 * )
 */
class D6Webform extends DrupalSqlBase implements ImportAwareInterface, RollbackAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('webform', 'wf');
    $query->innerJoin('node', 'n', 'wf.nid=n.nid');
    $query->innerJoin('node_revisions', 'nr', 'n.vid=nr.vid');

    $query->fields('wf', [
      'nid',
      'confirmation',
      'teaser',
      'status',
      'submit_text',
      'submit_limit',
      'submit_interval',
      'submit_notice',
      'confirmation_format',
      'allow_draft',
      'redirect_url',
      'block',
      'auto_save',
      'total_submit_limit',
      'total_submit_interval',
    ])
      ->fields('nr', [
        'title',
        'body',
        'format',
      ]
    );

    $query->addField('n', 'uid', 'node_uid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->filterDefaultFormat = $this->variableGet('filter_default_format', '1');
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'title' => $this->t('Webform title'),
      'body' => $this->t('Body'),
      'format' => $this->t('Body format'),
      'node_uid' => $this->t('Webform author'),
      'confirmation' => $this->t('Confirmation message'),
      'confirmation_type' => $this->t('Confirmation type'),
      'teaser' => $this->t('Teaser'),
      'status' => $this->t('Status'),
      'submit_text' => $this->t('Submission text'),
      'submit_limit' => $this->t('Submission limit'),
      'submit_interval' => $this->t('Submission interval'),
      'submit_notice' => $this->t('Submission notice'),
      'allow_draft' => $this->t('Draft submission allowed'),
      'redirect_url' => $this->t('Redirect url'),
      'block' => $this->t('Block'),
      'auto_save' => $this->t('Automatic save'),
      'total_submit_limit' => $this->t('Total submission limit'),
      'total_submit_interval' => $this->t('Total submission interval'),
      'webform_id' => $this->t('Id to be used for  Webform'),
      'elements' => $this->t('Elements for the Webform'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $elements = '';

    $nid = $row->getSourceProperty('nid');
    $webform = $this->buildFormElements($nid);
    $elements .= $webform['elements'];
    $handlers = $this->buildEmailHandlers($nid, $webform['xref']);
    $access = $this->buildAccessTable($nid);

    $confirm = $row->getSourceProperty('redirect_url');
    if ($confirm == '<confirmation>') {
      $confirm_type = 'page';
      $row->setSourceProperty('redirect_url', '');
    }
    elseif ($confirm == '<none>') {
      $confirm_type = 'inline';
      $row->setSourceProperty('redirect_url', '');
    }
    else {
      $confirm_type = 'url';
    }
    if ($row->getSourceProperty('submit_limit') < 0) {
      $row->setSourceProperty('submit_limit', '');
    }
    if ($row->getSourceProperty('total_submit_limit') < 0) {
      $row->setSourceProperty('total_submit_limit', '');
    }
    $row->setSourceProperty('confirmation_type', $confirm_type);
    $row->setSourceProperty('elements', $elements);
    $row->setSourceProperty('handlers', $handlers);
    $row->setSourceProperty('access', $access);
    $row->setSourceProperty('webform_id', 'webform_' . $nid);
    $row->setSourceProperty('status', $row->getSourceProperty('status') ? 'open' : 'closed');
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'wf';
    return $ids;
  }

  /**
   * Build form elements from webform component table.
   */
  private function buildFormElements($nid) {
    $output = '';

    $query = $this->select('webform_component', 'wc');
    $query->fields('wc', [
      'nid',
      'cid',
      'pid',
      'form_key',
      'name',
      'type',
      'value',
      'extra',
      'mandatory',
      'weight',
    ]);
    $components = $query->condition('nid', $nid)->orderBy('pid')->orderBy('weight')->execute();
    $children = [];
    $parents = [];
    $elements = [];
    $xref = [];

    // Build an array of elements in the correct order for rendering based on
    // pid and weight and a cross reference array to match cid with form_key
    // used by email handler.
    $multiPage = FALSE;
    foreach ($components as $component) {
      $xref[$component['cid']] = $component['form_key'];
      if ($component['type'] == 'pagebreak') {
        // Pagebreak found so we have a multi-page form.
        $multiPage = TRUE;
      }
      $children[$component['pid']][] = $component['cid'];
      $parents[$component['cid']][] = $component['pid'];
      $elements[$component['cid']] = $component;
    }
    // Keeps track of the parents we have to process, the last entry is used
    // for the next processing step.
    $process_parents = [];
    $process_parents[] = 0;
    $elements_tree = [];
    // Loops over the parent components and adds its children to the tree array.
    // Uses a loop instead of a recursion, because it's more efficient.
    while (count($process_parents)) {
      $parent = array_pop($process_parents);
      // The number of parents determines the current depth.
      $depth = count($process_parents);
      if (!empty($children[$parent])) {
        $has_children = FALSE;
        $child = current($children[$parent]);
        do {
          if (empty($child)) {
            break;
          }
          $element = $elements[$child];
          $element['depth'] = $depth;
          unset($element['pid']);
          $elements_tree[] = $element;
          if (!empty($children[$element['cid']])) {
            $has_children = TRUE;
            // We have to continue with this parent later.
            $process_parents[] = $parent;
            // Use the current component as parent for the next iteration.
            $process_parents[] = $element['cid'];
            // Reset pointers for child lists because we step in there more often
            // with multi parents.
            reset($children[$element['cid']]);
            // Move pointer so that we get the correct term the next time.
            next($children[$parent]);
            break;
          }
        } while ($child = next($children[$parent]));

        if (!$has_children) {
          // We processed all components in this hierarchy-level.
          reset($children[$parent]);
        }
      }
    }
    // If form has multiple pages then start first page automatically.
    if ($multiPage) {
      $pageCnt = 1;
      $current_page = 'wizard_page_1';
      $output .= "first_page:\n  '#type': wizard_page\n  '#title': {" . $current_page . "_title}\n  '#open': true\n";
      $current_page_title = t('Page') . ' ' . $pageCnt++;
    }

    foreach ($elements_tree as $element) {
      // If this is a multi-page form then indent all elements one level
      // to allow for page elements.
      if ($multiPage && $element['type'] != 'pagebreak') {
        $element['depth'] += 1;
      }
      $indent = str_repeat(' ', $element['depth'] * 2);
      $extra = unserialize($element['extra']);
      $description = $this->cleanString($extra['description']);

      // Create an option list if there are items for this element.
      $options = '';
      $valid_options = [];
      if (!empty($extra['items'])) {
        $items = explode("\n", trim($extra['items']));
        $ingroup = '';
        foreach ($items as $item) {
          $item = trim($item);
          if (!empty($item)) {
            if (preg_match('/^<(.*)>$/', $item, $matches)) {
              // Handle option groups.
              $options .= "$indent    '" . $matches[1] . "':\n";
              $ingroup = str_repeat(' ', 2);
            }
            else {
              $option = explode('|', $item);
              $valid_options[] = $option[0];
              if (count($option) == 2) {
                $options .= "$indent$ingroup    " . $option[0] . ": " . $option[1] . "\n";
              }
              else {
                $options .= "$indent$ingroup    " . $option[0] . ": " . $option[0] . "\n";
              }
            }
          }
        }
      }

      // Replace any tokens in the value.
      if (!empty($element['value'])) {
        $element['value'] = $this->replaceTokens($element['value']);
      }

      $markup = $indent . $element['form_key'] . ":\n";
      switch ($element['type']) {
        case 'fieldset':
          if ($multiPage && empty($current_page_title)) {
            $current_page_title = $element['name'];
          }
          $markup .= "$indent  '#type': fieldset\n$indent  '#open': true\n";
          break;

        case 'textfield':
          $markup .= "$indent  '#type': textfield\n";
          if (!empty($extra['width'])) {
            $markup .= "$indent  '#size': " . $extra['size'] . "\n";
          }
          break;

        case 'textarea':
          $markup .= "$indent  '#type': textarea\n";
          break;

        case 'select':
          if (!empty($extra['aslist'])) {
            $select_type = 'select';
          }
          elseif (!empty($extra['multiple'])) {
            $select_type = 'checkboxes';
          }
          else {
            $select_type = 'radios';
          }
          $markup .= "$indent  '#type': $select_type\n";
          $markup .= "$indent  '#options':\n" . $options;
          if (!empty($extra['multiple'])) {
            $markup .= "$indent  '#multiple': true\n";
          }
          break;

        case 'email':
          $markup .= "$indent  '#type': email\n$indent  '#size': 20\n";
          break;

        case 'number':
          if ($extra['type'] == 'textfield') {
            $markup .= "$indent  '#type': textfield\n$indent  '#size': 20\n";
          }
          elseif ($extra['type'] == 'select') {
            $markup .= "$indent  '#type': select\n";
            $markup .= "$indent  '#options':\n" . $options;
            $min = $extra['min'];
            $max = $extra['max'];
            $step = !empty($extra['step']) ? $extra['step'] : 1;
            for ($value = $min; $value <= $max; $value += $step) {
              $markup .= "$indent    " . $value . ": " . $value . "\n";
            }
          }
          if (isset($extra['min'])) {
            $markup .= "$indent  '#min': " . $extra['min'] . "\n";
          }
          if (isset($extra['max'])) {
            $markup .= "$indent  '#max': " . $extra['max'] . "\n";
          }
          if (isset($extra['step'])) {
            $markup .= "$indent  '#step': " . $extra['step'] . "\n";
          }
          if (isset($extra['unique'])) {
            $unique = ($extra['unique']) ? 'true' : 'false';
            $markup .= "$indent  '#unique': " . $unique . "\n";
          }
          break;

        case 'markup':
          $markup .= "$indent  '#type': processed_text\n$indent  '#format': full_html\n$indent  '#text': \"" . $this->cleanString($element['value']) . "\"\n";
          $element['value'] = '';
          break;

        case 'file':
          $exts = '';
          if (!empty($extra['filtering']['types'])) {
            $types = $extra['filtering']['types'];
            if (!empty($extra['filtering']['addextensions'])) {
              $add_types = explode(',', $extra['filtering']['addextensions']);
              $types = array_unique(array_merge($types, array_map('trim', $add_types)));
            }
            $exts = implode(',', $types);
          }
          $filesize = '';
          if (!empty($extra['filtering']['size'])) {
            $filesize = $extra['filtering']['size'] / 1000;
          }
          $markup .= "$indent  '#type': managed_file\n";
          $markup .= "$indent  '#max_filesize': '$filesize'\n";
          $markup .= "$indent  '#file_extensions': '$exts'\n";
          if (!empty($extra['width'])) {
            $markup .= "$indent  '#size': " . $extra['width'] . "\n";
          }
          break;

        case 'date':
          $markup .= "$indent  '#type': date\n";
          /*if (!empty($element['value'])) {
          $element['value'] = date('Y-m-d', strtotime($element['value']));
          }*/
          break;

        case 'time':
          $markup .= "$indent  '#type': time\n";
          if (!empty($extra['hourformat'])) {
            if ($extra['hourformat'] == '12-hour') {
              $markup .= "$indent  '#time_format': 'g:i A'\n";
            }
            elseif ($extra['hourformat'] == '24-hour') {
              $markup .= "$indent  '#time_format': 'H:i'\n";
            }
          }
          /*if (!empty($element['value'])) {
          $element['value'] = date('c', strtotime($element['value']));
          }*/
          break;

        case 'hidden':
          $markup .= "$indent  '#type': hidden\n";
          break;

        case 'pagebreak':
          $output = str_replace('{' . $current_page . '_title}', $current_page_title, $output);
          $current_page = $element['form_key'];
          $markup .= "$indent  '#type': wizard_page\n  '#open': true\n  '#title': {" . $current_page . "_title}\n";
          $current_page_title = t('Page') . ' ' . $pageCnt++;
          break;
      }

      if (!empty($element['type']) && is_string($element['type'])) {
        $this->getModuleHandler()->alter('webform_migrate_d6_weform_element_' . $element['type'], $markup, $indent, $element);
      }

      // Add common fields.
      if (!empty($element['value']) && (empty($valid_options) || in_array($element['value'], $valid_options))) {
        $markup .= "$indent  '#default_value': " . $element['value'] . "\n";
      }
      if (!empty($extra['field_prefix'])) {
        $markup .= "$indent  '#field_prefix': " . $extra['field_prefix'] . "\n";
      }
      if (!empty($extra['field_suffix'])) {
        $markup .= "$indent  '#field_suffix': " . $extra['field_suffix'] . "\n";
      }
      if (!empty($extra['title_display']) && $extra['title_display'] != 'before') {
        $title_display = $extra['title_display'];
        if ($title_display == 'none') {
          $title_display = 'invisible';
        }
        $markup .= "$indent  '#title_display': " . $title_display . "\n";
      }
      if ($element['type'] != 'pagebreak') {
        $markup .= "$indent  '#title': " . $element['name'] . "\n";
        $markup .= "$indent  '#description': \"" . $description . "\"\n";
      }
      if (!empty($element['mandatory'])) {
        $markup .= "$indent  '#required': true\n";
      }

      $output .= $markup;
    }

    if ($multiPage) {
      // Replace the final page title.
      $output = str_replace('{' . $current_page . '_title}', $current_page_title, $output);
    }

    return ['elements' => $output, 'xref' => $xref];
  }

  /**
   * Build email handlers from webform emails table.
   */
  private function buildEmailHandlers($nid, $xref) {

    $query = $this->select('webform_emails', 'we');
    $query->fields('we', [
      'nid',
      'eid',
      'email',
      'subject',
      'from_name',
      'from_address',
      'template',
      'excluded_components',
      'html',
      'attachments',
    ]);
    $emails = $query->condition('nid', $nid)->execute();

    $handlers = [];
    foreach ($emails as $email) {
      $id = 'email_' . $email['eid'];
      foreach (['email', 'subject', 'from_name', 'from_address'] as $field) {
        if (!empty($email[$field]) && is_numeric($email[$field]) && !empty($xref[$email[$field]])) {
          $email[$field] = "[webform-submission:values:{$xref[$email[$field]]}:raw]";
        }
      }
      $excluded = [];
      if (!empty($email['excluded_components'])) {
        $excludes = explode(',', $email['excluded_components']);
        foreach ($excludes as $exclude) {
          if (!empty($xref[$exclude])) {
            $excluded[$xref[$exclude]] = $xref[$exclude];
          }
        }
      }
      $handlers[$id] = [
        'id' => 'email',
        'label' => 'Email ' . $email['eid'],
        'handler_id' => $id,
        'status' => 1,
        'weight' => $email['eid'],
        'settings' => [
          'to_mail' => $email['email'],
          'from_mail' => $email['from_address'],
          'from_name' => $email['from_name'],
          'subject' => $email['subject'],
          'body' => $email['template'],
          'html' => $email['html'],
          'attachments' => $email['attachments'],
          'excluded_elements' => $excluded,
        ],
      ];
    }
    return $handlers;
  }

  /**
   * Build access table from webform roles table.
   */
  private function buildAccessTable($nid) {

    $query = $this->select('webform_roles', 'wr');
    $query->innerJoin('role', 'r', 'wr.rid=r.rid');
    $query->fields('wr', [
      'nid',
      'rid',
    ])
      ->fields('r', [
        'name',
      ]
    );
    $wf_roles = $query->condition('nid', $nid)->execute();

    $roles = [];
    // Handle rids 1 and 2 as per user_update_8002.
    $map = [
      1 => 'anonymous',
      2 => 'authenticated',
    ];
    foreach ($wf_roles as $role) {
      if (isset($map[$role['rid']])) {
        $roles[] = $map[$role['rid']];
      }
      else {
        $roles[] = str_replace(' ', '_', strtolower($role['name']));
      }
    }

    $access = [
      'create' => [
        'roles' => $roles,
        'users' => [],
      ],
    ];

    return $access;
  }

  /**
   * Translate webform tokens into regular tokens.
   *
   * %uid - The user id (unsafe)
   * %username - The name of the user if logged in.
   *                       Blank for anonymous users. (unsafe)
   * %useremail - The e-mail address of the user if logged in.
   *                       Blank for anonymous users. (unsafe)
   * %ip_address - The IP address of the user. (unsafe)
   * %site - The name of the site
   *             (i.e. Northland Pioneer College, Arizona) (safe)
   * %date - The current date, formatted according
   *              to the site settings.(safe)
   * %nid - The node ID. (safe)
   * %title - The node title. (safe)
   * %sid - The Submission id (unsafe)
   * %submission_url - The Submission url (unsafe)
   * %profile[key] - Any user profile field or value, such as %profile[name]
   *                         or %profile[profile_first_name] (unsafe)
   * %get[key] - Tokens may be populated from the URL by creating URLs of
   *                    the form http://example.com/my-form?foo=bar.
   *                    Using the token %get[foo] would print "bar". (safe)
   * %post[key] - Tokens may also be populated from POST values
   *                      that are submitted by forms. (safe)
   * %email[key] (unsafe)
   * %value[key] (unsafe)
   * %email_values (unsafe)
   * %cookie[key] (unsafe)
   * %session[key] (unsafe)
   * %request[key] (unsafe)
   * %server[key] (unsafe)
   *
   * Safe values are available to all users and unsafe values
   * should only be shown to authenticated users.
   */
  private function replaceTokens($str) {
    return $str;
  }

  /**
   * {@inheritdoc}
   */
  private function cleanString($str) {
    return str_replace(['"', "\n", "\r"], ["'", '\n', ''], $str);
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(MigrateImportEvent $event) {}

  /**
   * {@inheritdoc}
   */
  public function postImport(MigrateImportEvent $event) {
    // Add the Webform field to the webform content type
    // if it doesn't already exist.
    $field_storage = FieldStorageConfig::loadByName('node', 'webform');
    $field = FieldConfig::loadByName('node', 'webform', 'webform');
    if (empty($field)) {
      $field = \Drupal::service('entity_type.manager')->getStorage('field_config')->create([
        'field_storage' => $field_storage,
        'bundle' => 'webform',
        'label' => 'Webform',
        'settings' => [],
      ]);
      $field->save();
      // Assign widget settings for the 'default' form mode.
      $display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'webform', 'default')->getComponent('webform');
      \Drupal::service('entity_display.repository')->getFormDisplay('node', 'webform', 'default')
        ->setComponent('webform', [
          'type' => $display['type'],
        ])
        ->save();
      // Assign display settings for the 'default' and 'teaser' view modes.
      $display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'webform', 'default')->getComponent('webform');
      \Drupal::service('entity_display.repository')->getViewDisplay('node', 'webform', 'default')
        ->setComponent('webform', [
          'label' => $display['label'],
          'type' => $display['type'],
        ])
        ->save();
      // The teaser view mode is created by the Standard profile and therefore
      // might not exist.
      $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');
      if (isset($view_modes['teaser'])) {
        $display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'webform', 'teaser')->getComponent('webform');
        \Drupal::service('entity_display.repository')->getViewDisplay('node', 'webform', 'teaser')
          ->setComponent('webform', [
            'label' => $display['label'],
            'type' => $display['type'],
          ])
          ->save();
      }
    }

    // Attach any Webform created to the relevant webforms if
    // Webform exists and Webform exists and Webform field is empty.
    $webforms = $this->query()->execute();
    foreach ($webforms as $webform) {
      $webform_nid = $webform['nid'];
      $webform_id = 'webform_' . $webform_nid;
      $webform = Webform::load($webform_id);
      if (!empty($webform)) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = Node::load($webform_nid);
        if (!empty($node) && $node->getType() == 'webform') {
          if (empty($node->webform->target_id)) {
            $node->webform->target_id = $webform_id;
            $node->webform->status = 1;
            $node->save();
          }
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preRollback(MigrateRollbackEvent $event) {}

  /**
   * {@inheritdoc}
   */
  public function postRollback(MigrateRollbackEvent $event) {
    // Remove any Webform from webform if webform no longer exists.
    $webforms = $this->query()->execute();
    foreach ($webforms as $webform) {
      $webform_nid = $webform['nid'];
      $webform_id = 'webform_' . $webform_nid;
      $webform = Webform::load($webform_id);
      if (empty($webform)) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = Node::load($webform['nid']);
        if (!empty($node) && $node->getType() == 'webform') {
          if (!empty($node->webform->target_id) && $node->webform->target_id == $webform_id) {
            $node->webform->target_id = NULL;
            $node->save();
          }
        }
      }
    }
  }

}
