/**
 * @file
 * Drupal Font Awesome plugin.
 *
 * @ignore
 */

(($, Drupal, drupalSettings, CKEDITOR) => {
  'use strict';

  CKEDITOR.plugins.add('drupalfontawesome', {
    icons: 'drupalfontawesome',
    hidpi: true,

    init(editor) {
      // Add the command for inserting Font Awesome icons.
      editor.addCommand('drupalfontawesome', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec(execEditor) {
          // Prepare a save callback to be used upon saving the dialog.
          const saveCallback = (returnValues) => {
            execEditor.fire('saveSnapshot');

            // Create a new icon element if needed.
            const selection = execEditor.getSelection();
            const range = selection.getRanges(1)[0];

            // Create the container span for the icon.
            var container = new CKEDITOR.dom.element('span', execEditor.document);
            container.addClass('fontawesome-icon-inline');
            // Create the icon element from the editor.
            var icon = new CKEDITOR.dom.element(returnValues.tag, execEditor.document);
            icon.setAttributes(returnValues.attributes);
            // Add the icon to the container.
            container.append(icon);
            // CKEditor doesn't play well with SVG - this allows editing.
            container.appendHtml('&nbsp;');

            // Add the container to the range.
            range.insertNode(container);
            range.select();

            // Save snapshot for undo support.
            execEditor.fire('saveSnapshot');

            // Fire custom event so we can reload SVGs.
            execEditor.fire('insertedIcon');
          };

          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          const dialogSettings = {
            title: execEditor.config.drupalFontAwesome_dialogTitleAdd,
            dialogClass: 'fontawesome-icon-dialog',
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(execEditor, Drupal.url(`fontawesome/dialog/icon/${execEditor.config.drupal.format}`), {}, saveCallback, dialogSettings);
        },
      });

      // Add button for icons.
      if (editor.ui.addButton) {
        editor.ui.addButton('DrupalFontAwesome', {
          label: Drupal.t('Font Awesome'),
          command: 'drupalfontawesome',
        });
      }
    },
  });

  // Allow empty tags in the CKEditor since Font Awesome requires them.
  if ('editor' in drupalSettings && 'fontawesome' in drupalSettings.editor) {
    $.each(drupalSettings.editor.fontawesome.allowedEmptyTags, (_, tag) => {
      CKEDITOR.dtd.$removeEmpty[tag] = 0;
    });
  }

  // Define FontAwesome conversion functions.
  Drupal.FontAwesome = {};

  // Converts HTML tags to SVG by loading the attached libraries.
  Drupal.FontAwesome.tagsToSvg = (drupalSettings, thisEditor) => {
    if ('editor' in drupalSettings && 'fontawesome' in drupalSettings.editor) {
      // Loop over each SVG library and include them. These convert the tags.
      $.each(drupalSettings.editor.fontawesome.fontawesomeLibraries, (_, library) => {
        // Create a script.
        const $script = document.createElement('script');
        const $editorInstance = CKEDITOR.instances[thisEditor.editor.name];
        // Point the script at our library.
        $script.src = library;

        $editorInstance.document.getHead().$.appendChild($script);
      });
    }
  };

  // Converts the resulting SVG tags back to their original HTML tags.
  Drupal.FontAwesome.svgToTags = (thisEditor) => {
    // Get the current body of text.
    let htmlBody = thisEditor.editor.getData();
    // Turn the SVGs back into their original icons.
    htmlBody = htmlBody.replace(/<svg .*?class="svg-inline--fa.*?<\/svg><!--\s?(<(span|i).*?<\/(span|i)>).*s?-->/g, '$1');
    // Set the body to the new value.
    thisEditor.editor.setData(htmlBody);
  };

  // After CKEditor is ready.
  CKEDITOR.on(
    'instanceReady',
    (ev) => {
      // On initial load, convert icons to SVGs.
      Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);

      // On mode change, deal with the changes on the fly.
      ev.editor.on('mode', () => {
        if (ev.editor.mode === 'source') {
          // If we are showing source, turn SVG back to original tags.
          Drupal.FontAwesome.svgToTags(ev);
        }
        else if (ev.editor.mode === 'wysiwyg') {
          // If switching back to the display mode, have to load SVGs again.
          Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);
        }
      });

      // Listen to the event for inserting icons from the plugin.
      ev.editor.on('insertedIcon', () => {
        // todo: For some reason this throws an 'Uncaught TypeError'.
        // Force an update to the content.
        ev.editor.setData(ev.editor.getData());
        // Then reload the SVGs.
        Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);
      });
    },
  );
})(jQuery, Drupal, drupalSettings, CKEDITOR);
