<?php

/**
 * @file
 * Hooks for the Name field module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide widget layout options.
 *
 * By implementing hook_name_widget_layouts(), a module can provide additional
 * layout options for a name field element.
 *
 * For usage example, see hook_name_widget_layouts().
 *
 * @return array
 *   A keyed array of layout settings.
 *   - label: The layout label (required).
 *   - library: An array of libraries to attach to the element.
 *   - wrapper_attributes: An array of wrapper attributes.
 */
function hook_name_widget_layouts() {
  return [
    'inline' => [
      'label' => t('Inline'),
      'library' => [
        'name/widget.inline',
      ],
      'wrapper_attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
