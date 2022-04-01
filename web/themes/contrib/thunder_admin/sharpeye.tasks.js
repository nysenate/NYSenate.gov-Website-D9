const options = require('./sharpeye.conf').options;

module.exports = [
  {name: 'Login', path: '/user/login', noScreenshot: true, actions: [
    {fill: [
      {$: 'form#user-login-form [name="name"]', value: options.user},
      {$: 'form#user-login-form [name="pass"]', value: options.pass}
    ]},
    {$: 'form#user-login-form input[name="op"]'}
  ]},
  {name: 'Disable autosaving', path: '/admin/config/content/autosave_form', noScreenshot: true, actions: [
    {$: '[data-drupal-selector="edit-active-on-content-entity-forms"]'},
    {$: '[data-drupal-selector="edit-submit"]'}
  ]},
  {name: 'Content', path: '/admin/content', fullPage: true, replace: [
    {$: 'td.views-field.views-field-changed', value: '01/01/2018 - 00:00'}
  ], actions: [
    {$: '#view-title-table-column a', waitBefore: 1000}
  ]},
  {path: '/admin/content/scheduled', fullPage: true},
  {name: 'Files', path: '/admin/content/files', fullPage: true, replace: [
    {$: '.views-field-filesize', value: '99.9 KB'},
    {$: '.views-field-created, .views-field-changed', value: 'Mon, 07/08/2019 - 08:27'}
  ], actions: [
    {$: '#view-filename-table-column a'},
    {$: 'div#block-thunder-admin-page-title h1'}
  ]},
  {path: '/node/add'},
  {path: '/node/add/article', fullPage: true, replace: [{$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}]},
  {name: 'Meta tags token browser', path: '/node/add/article', actions: [
    {$: '#edit-field-meta-tags-0 [role=button]', wait: '#edit-field-meta-tags-0-metatag-async-widget-customize-meta-tags'},
    {$: '#edit-field-meta-tags-0-metatag-async-widget-customize-meta-tags', wait: '[data-drupal-selector="edit-field-meta-tags-0-basic"]'},
    {$: '.token-dialog', wait: '.token-tree'}
  ]},
  {name: 'Add paragraphs modal', path: '/node/add/article', fullPage: true, replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ], actions: [
    {$: '#field-paragraphs-values > tbody > tr > td > div > ul > li > button'}
  ]
  },
  {name: 'Paragraphs', path: '/node/add/article', fullPage: true, replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ], actions: [
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_text_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-0-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_quote_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-1-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_link_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-2-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_twitter_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-3-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_gallery_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-4-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_image_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-5-subform"]'},
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_video_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-6-subform"]'}
  ]
  },
  {name: 'Linkit dialog', path: '/node/add/article', fullPage: true, replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ], actions: [
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_text_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-0-subform"]'},
    {$: '//*[contains(@class, "cke_button__drupallink")]/span[1]'},
    {$: '.ui-dialog-buttonpane'}
  ]
  },
  {name: 'Paragraphs modified content message', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {$: '#field-paragraphs-1-edit--2', wait: '.cke_button__bulletedlist'},
    {$: '.cke_button__bulletedlist'},
    {$: '[name="field_paragraphs_1_collapse"]', waitBefore: 500, wait: '[data-drupal-selector="edit-field-paragraphs-1-top-icons"] .paragraphs-icon-changed'},
    {$: '[data-drupal-selector="edit-field-paragraphs-1-top-icons"] .paragraphs-icon-changed'}
  ]},
  {name: 'CKEditor dialog', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', element: '.cke_dialog', actions: [
    {$: '[data-drupal-selector="field-paragraphs-1-edit-2"]', wait: '.paragraph-form-item--has-subform'},
    {$: '//select[@data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-format"]/option[@value=\'full_html\']'},
    {$: '//div[contains(@class,"editor-change-text-format-modal")]/div[3]/div/button[1]', wait: 'div[id^=cke_edit-field-paragraphs-1-subform-field-text-0-value]'},
    {$: '//*[contains(@class,"cke_button_off") and @title="Table"]'},
    {$: '//select[contains(@class, "cke_dialog_ui_input_select")]'},
    {$: '//select[contains(@class, "cke_dialog_ui_input_select")]', waitBefore: 500}
  ]},
  {name: 'Entity browser gallery', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', actions: [
    {$: '[data-drupal-selector="field-paragraphs-0-edit-2"]', wait: '.paragraph-form-item--has-subform'},
    {$: '[data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-entity-browser-entity-browser-open-modal"]', wait: 'iframe[name="entity_browser_iframe_multiple_image_browser"]'},
    {switchToFrame: 'iframe[name="entity_browser_iframe_multiple_image_browser"]'},
    {switchToFrame: null, waitBefore: 1000}
  ]},
  {name: 'Entity browser remove', path: '/edit/node/0bd5c257-2231-450f-b4c2-ab156af7b78d', remove: ['.ui-dialog-content .ajax-progress-throbber'], replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {$: '[data-drupal-selector="field-paragraphs-0-edit-2"]', wait: '.paragraph-form-item--has-subform'},
    {$: '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-current-items-0-remove-button"]', wait: '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-entity-browser-entity-browser-open-modal"]'},
    {$: '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-entity-browser-entity-browser-open-modal"]'},
    {switchToFrame: 'iframe[name="entity_browser_iframe_image_browser"]'},
    {switchToFrame: null, waitBefore: 1000}
  ]},
  {name: 'Nested table sort', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {$: '//*[@id="field-paragraphs-values"]/tbody/tr[7]/td/div/ul/li/button'},
    {$: '//div[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]/ul/li/input[@name="field_paragraphs_link_add_more"]'},
    {fill: [
      {$: '//input[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-0-uri"]', value: 'http://example.com/1'}
    ]},
    {$: '//input[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-add-more"]'},
    {fill: [
      {$: '//input[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-1-uri"]', value: 'http://example.com/2'}
    ]},
    {$: '//*[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-wrapper"]/div/div/table/thead/tr[2]/th/button'},
    {$: '//*[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-wrapper"]/div/div/table/tbody/tr[4]/td[1]/input'},
    {$: '//*[@data-drupal-selector="edit-field-paragraphs-5-subform-field-link-wrapper"]/div/div/table/tbody/tr[1]/td/a'}
  ]},
  {name: 'Modals in paragraphs', path: '/node/add/article', fullPage: true, actions: [
    {$: '.field-multiple-table--paragraphs > tbody > tr:last-of-type .paragraphs-features__add-in-between__button', wait: '.paragraphs-add-dialog.ui-dialog-content '},
    {$: '.paragraphs-add-dialog.ui-dialog-content [name="field_paragraphs_image_add_more"]', wait: '[data-drupal-selector="edit-field-paragraphs-0-subform"]'},
    {$: '[name="field_paragraphs_0_subform_field_image_entity_browser_entity_browser"]'},
    {switchToFrame: 'iframe[name="entity_browser_iframe_image_browser"]', wait: '#entity-browser-image-browser-form'},
    {$: '#entity-browser-image-browser-form .view-content > div:nth-child(1)'},
    {switchToFrame: null, waitBefore: 1000}
  ]},
  {path: '/node/add/page', replace: [{$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}], actions: [
    {wait: '.cke_wysiwyg_frame'}
  ]},
  {name: 'Media', path: '/admin/content/media', fullPage: true, replace: [{$: '//td[contains(@class, "views-field-changed")]/text()', value: ''}], actions: [
    {$: '#view-name-table-column a'},
    {waitBefore: 500}
  ]},
  {path: '/media/add'},
  {name: 'Media type gallery edit form', path: '/edit/media/d65746ed-6b92-498d-9100-8603be730c71', replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ], actions: [
    {moveto: {$: '#block-thunder-admin-page-title'}}
  ]
  },
  {name: 'Media type image edit form', path: '/edit/media/17965877-27b2-428f-8b8c-7dccba9786e5', fullPage: true, replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ]},
  {name: 'Media type twitter edit form', path: '/edit/media/929b164b-8718-494a-9769-853a6f758a81', replace: [
    {$: '//*[@id="edit-author"]/summary/span/text()', value: ' (Authored on xxxx-xx-xx)'}
  ]},
  {name: 'Media type video add form', path: '/media/add/video', fullPage: true, replace: [
    {$: '//*[@id="media-video-add-form"]/div[2]/div/ul/li[2]/a/span', value: 'Authored on xxxx-xx-xx'}
  ]
  },
  {name: 'Status page', path: '/admin/reports/status', fullPage: true, remove: ['#block-thunder-admin-content > div.system-status-report > div:nth-child(2) > details:nth-of-type(1):not(:only-of-type)'], replace: [
    {$: '//*[@id="block-thunder-admin-content"]/div[1]/div[1]/span/span[2]/span[1]', value: 'X Errors'},
    {$: '//*[@id="block-thunder-admin-content"]/div[1]/div[2]/span/span[2]/span[1]', value: 'X Warnings'},
    {$: '//*[@id="block-thunder-admin-content"]/div[1]/div[3]/span/span[2]/span[1]', value: 'X Checked'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[1]/div/text()[1]', value: '9.x.x'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[2]/div/text()[1]', value: 'Last run 00 hours 00 minutes ago'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[3]/div/text()[1]', value: 'Apache/x.x.xx (Unix) OpenSSL/x.x.x mod_fcgid/x.x.x\n'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[4]/div/text()[1]', value: '7.x.xx ('},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[4]/div/text()[3]', value: 'xxxM'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[5]/div/text()[1]', value: 'x.x.x-xx.x-log\n\n'},
    {$: '//*[@id="block-thunder-admin-content"]/div[2]/div/div[5]/div/text()[2]', value: 'MySQL, MariaDB, Percona Server, or equivalent\n\n'},
    {$: 'h3#checked ~ details div', value: ' '}
  ]},
  {name: 'Admin structure block', path: '/admin/structure/block', fullPage: true, actions: [
    {$: 'div#block-thunder-admin-page-title h1'}
  ]},
  {name: 'Place block modal', path: '/admin/structure/block', element: '.ui-dialog', actions: [
    {$: 'a#edit-blocks-region-header-title', wait: '.ui-dialog'}
  ]},
  {path: '/admin/structure/block/manage/thunder_base_branding'},
  {name: 'Taxonomy term ordering', path: '/admin/structure/taxonomy/manage/channel/overview', actions: [
    {dragAndDrop: '//tr[contains(@class, "draggable")][2]/td/a[@class="tabledrag-handle"]', offsetx: 150},
    {$: '//div[contains(@class, "tabledrag-changed-warning messages")]', waitBefore: 1000}
  ]},
  {path: '/edit/taxonomy_term/bfc251bc-de35-467d-af44-1f7a7012b845'},
  {path: '/admin/structure/types/manage/article', fullPage: true},
  {path: '/admin/structure/types/manage/article/fields'},
  {path: '/admin/structure/types/manage/article/form-display', fullPage: true},
  {name: 'Article display', path: '/admin/structure/types/manage/article/display', fullPage: true, hide: [
    '.form-item-fields-field-channel-type',
    '.form-item-fields-field-teaser-media-type'
  ]},
  {name: 'Admin structure', path: '/admin/structure', fullPage: true, actions: [
    {moveto: {$: '#block-thunder-admin-page-title'}}
  ]},
  {name: 'Appearance', path: '/admin/appearance', fullPage: true, remove: ['.system-themes-list-uninstalled > .theme-selector:not(:first-of-type)'], replace: [
    {$: 'h3.theme-info__header', value: 'Theme name'}
  ]},
  {path: '/admin/modules', fullPage: true, replace: [
    {$: '//*[@id="edit-modules-stress-test-enable-description"]/summary/span/text()', value: 'This version is not compatible with Drupal 9.x and should be replaced.'}
  ]},
  {path: '/admin/config', fullPage: true},
  {path: '/admin/config/development/performance'},
  {name: 'System Information', path: '/admin/config/system/site-information', fullPage: true, hide: ['#edit-front-page .field-prefix'], replace: [
    {$: '//*[@id="edit-site-403"]/@value', value: 'node/403'},
    {$: '//*[@id="edit-site-404"]/@value', value: 'node/404'}
  ]},
  {name: 'Input format Basic HTML', path: '/admin/config/content/formats/manage/basic_html', fullPage: true, actions: [
    {wait: '#editor-settings-wrapper li.vertical-tabs__menu-item.first span.vertical-tabs__menu-item-summary'}
  ]},
  {name: 'Install page', path: '/core/install.php', hide: ['.site-version']},
  {name: 'Select2 dropdown', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, hide: [
    '.select2-search__field'
  ], replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {fill: [
      {$: 'input.select2-search__field', value: 'abc'}
    ]},
    {waitBefore: 1000}
  ]},
  {name: 'Select2 selection', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {fill: [
      {$: 'input.select2-search__field', value: 'abc'}
    ]},
    {$: 'label[for=edit-field-tags]', waitBefore: 300},
    {$: '#edit-group-basis .fieldset-legend'}
  ]},
  {name: 'Configure details element as field group', path: '/admin/structure/types/manage/article/form-display', fullPage: true, actions: [
    {$: '//a[@data-drupal-link-system-path="admin/structure/types/manage/article/form-display/add-group"]'},
    {$: '//select[@data-drupal-selector="edit-group-formatter"]/option[@value="details"]'},
    {fill: [
      {$: '//input[@data-drupal-selector="edit-label"]', value: 'Basis Details'}
    ]},
    {$: '//input[@data-drupal-selector="edit-submit"]', waitBefore: 1000},
    {fill: [
      {$: '//input[@data-drupal-selector="edit-format-settings-classes"]', value: 'content-form__form-section'}
    ]},
    {$: '//input[@data-drupal-selector="edit-submit"]', waitBefore: 1000},
    {dragAndDrop: '//tr[@data-drupal-selector="edit-fields-group-basis-details"]/td/a[@class="tabledrag-handle"]', offsety: -1400},
    {dragAndDrop: '//tr[@data-drupal-selector="edit-fields-field-channel"]/td/a[@class="tabledrag-handle"]', offsety: -50, waitBefore: 1000},
    {$: '//input[@data-drupal-selector="edit-submit"]', waitBefore: 1000}
  ]},
  {name: 'Check details element in frontend', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {$: '.field-group-details.content-form__form-section > summary', waitBefore: 1000}
  ]},
  {name: 'Cleanup details element as field group', path: '/admin/structure/types/manage/article/form-display', fullPage: true, actions: [
    {$: '//a[@href="/admin/structure/types/manage/article/form-display/group_basis_details/delete"]'},
    {$: '//input[@data-drupal-selector="edit-submit"]', waitBefore: 1000},
    {dragAndDrop: '//tr[@data-drupal-selector="edit-fields-field-channel"]/td/a[@class="tabledrag-handle"]', offsety: 50},
    {$: '//tr[@data-drupal-selector="edit-fields-field-channel"]/td/a[@class="tabledrag-handle"]'},
    {$: '//input[@data-drupal-selector="edit-submit"]', waitBefore: 1000}
  ]},
  {name: 'Thunder styleguide', path: '/admin/thunder-styleguide', fullPage: true},
  {name: 'Views UI', path: '/admin/structure/views/view/frontpage', fullPage: true, actions: [
    {$: '[data-drupal-selector="edit-displays-settings-settings-content-tab-content-details-columns-third"]'},
    {$: 'div#block-thunder-admin-page-title h1'}
  ]},
  {name: 'Views argument options', path: '/admin/structure/views/view/taxonomy_term', viewports: [{width: 1280, height: 1169}], actions: [
    {$: '[data-drupal-selector="edit-displays-settings-settings-content-tab-content-details-columns-third"]'},
    {$: '[data-drupal-selector="edit-displays-settings-settings-content-tab-content-details-columns-third-arguments"] .views-ui-display-tab-setting a.views-ajax-link', wait: '[data-drupal-selector="edit-options-argument-present"]'},
    {$: '[data-drupal-selector="edit-options-form-description"]'}
  ]},
  {name: 'Show description on form error', path: '/admin/structure/types/manage/article/form-display', fullPage: true, actions: [
    {$: '//input[@data-drupal-selector="edit-fields-field-tags-settings-edit"]'},
    {fill: [
      {$: '//input[@data-drupal-selector="edit-fields-field-tags-settings-edit-form-settings-width"]', value: 'abc', waitBefore: 1000}
    ]},
    {$: '//input[@data-drupal-selector="edit-fields-field-tags-settings-edit-form-actions-save-settings"]'},
    {$: '//div[@data-drupal-selector="edit-fields-field-tags-settings-edit-form"]', waitBefore: 1000}
  ]},
  {name: 'Views overlay and toolbar', path: '/admin/structure/views/view/content', actions: [
    {$: '#toolbar-item-administration-tray button.toolbar-icon-toggle-vertical'},
    {$: '[data-drupal-selector="edit-displays-settings-settings-content-tab-content-details-columns-third"]'},
    {$: '[data-drupal-selector="edit-displays-settings-settings-content-tab-content-details-columns-third-relationships"] .views-ui-display-tab-setting a.views-ajax-link', wait: '[data-drupal-selector="edit-options-required"]'}
  ]},
  {name: 'Nested paragraphs', path: '/node/10/edit', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'}
  ], actions: [
    {$: '#toolbar-item-administration'},
    {$: '#toolbar-item-administration'},
    {$: '#toolbar-item-administration-tray button.toolbar-icon-toggle-horizontal'},
    {$: 'input#field-paragraphs-0-edit--2', wait: '#field-paragraphs-0-subform-field-paragraph-add-more-wrapper'}
  ]},
  {name: 'Open sidebar elements', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', viewports: [{width: 1280, height: 1803}], replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'},
    {$: '//div[@data-drupal-messages=""]/div/ul/li[1]', value: 'This content is being edited by the user admin and is therefore locked to prevent other users changes. This lock is in place since X sec.'}
  ], actions: [
    {$: '#edit-options > summary'},
    {$: '#edit-author > summary'},
    {$: '#edit-url-redirects > summary'},
    {$: '#edit-scheduler-settings > summary'},
    {$: '#edit-simple-sitemap > summary'}
  ]},

  /* Content lock disabled form test, order is important. */
  {name: 'Trigger content lock', noScreenshot: true, path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07'},
  {name: 'Logout', noScreenshot: true, path: '/user/logout'},
  {name: 'Login', path: '/user/login', noScreenshot: true, actions: [
    {fill: [
      {$: 'form#user-login-form [name="name"]', value: options.editorUser},
      {$: 'form#user-login-form [name="pass"]', value: options.editorPass}
    ]},
    {$: 'form#user-login-form input[name="op"]', wait: '#toolbar-administration'}
  ]},
  {name: 'Content lock disabled form elements', path: '/edit/node/36b2e2b2-3df0-43eb-a282-d792b0999c07', fullPage: true, replace: [
    {$: '//*[@id="edit-meta-changed"]/text()', value: ' 01/01/2018 - 00:00'},
    {$: '//div[@data-drupal-messages=""]/div/ul/li[1]', value: 'This content is being edited by the user admin and is therefore locked to prevent other users changes. This lock is in place since X sec.'}
  ], actions: [
    {$: '#edit-author > summary'},
    {$: '#edit-scheduler-settings > summary'}
  ]},
  {name: 'Logout', noScreenshot: true, path: '/user/logout'},
  {name: 'Login', path: '/user/login', noScreenshot: true, actions: [
    {fill: [
      {$: 'form#user-login-form [name="name"]', value: options.user},
      {$: 'form#user-login-form [name="pass"]', value: options.pass}
    ]},
    {$: 'form#user-login-form input[name="op"]', wait: '#toolbar-administration'}
  ]},
  {name: 'Resize tabs', path: '/admin/structure/types/manage/article/display', viewports: [{width: 400, height: 2095}], hide: [
    '.form-item-fields-field-channel-type',
    '.form-item-fields-field-teaser-media-type',
    '#toolbar-item-administration-tray'
  ], actions: [
    {$: '//a[@data-toolbar-tray="toolbar-item-administration-tray"]'},
    {$: '//details[@data-drupal-selector="edit-modes"]'},
    {$: '//input[@data-drupal-selector="edit-display-modes-custom-diff"]'},
    {$: '//input[@data-drupal-selector="edit-display-modes-custom-full"]'},
    {$: '//input[@data-drupal-selector="edit-display-modes-custom-search-index"]'},
    {$: '//input[@data-drupal-selector="edit-display-modes-custom-search-result"]'},
    {$: '//input[@data-drupal-selector="edit-display-modes-custom-token"]'},
    {$: '//input[@data-drupal-selector="edit-submit"]'},
    {$: '//a[@data-toolbar-tray="toolbar-item-administration-tray"]'}
  ]},
  {name: 'Open tabs', path: '/admin/structure/types/manage/article/display', viewports: [{width: 399}], hide: [
    '.form-item-fields-field-channel-type',
    '.form-item-fields-field-teaser-media-type',
    '#toolbar-item-administration-tray'
  ], actions: [
    {$: '//a[@data-toolbar-tray="toolbar-item-administration-tray"]'},
    {$: '//button[contains(@class, "tabs__trigger")]'},
    {$: '//a[@data-toolbar-tray="toolbar-item-administration-tray"]'}
  ]}
];
