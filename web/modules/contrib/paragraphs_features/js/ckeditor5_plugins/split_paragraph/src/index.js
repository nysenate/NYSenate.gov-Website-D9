/**
 * @file
 * Split paragraph plugin.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import SplitParagraphCommand from './splitparagraphcommand';
import icon from '../../../../icons/split.svg';

class SplitParagraph extends Plugin {
  init() {
    // Register splitParagraph toolbar button.
    this.editor.ui.componentFactory.add('splitParagraph', (locale) => {
      const command = this.editor.commands.get('splitParagraph');
      const buttonView = new ButtonView(locale);

      // Create toolbar button.
      buttonView.set({
        label: this.editor.t('Split Paragraph'),
        icon,
        tooltip: true,
      });

      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute command when button is clicked.
      this.listenTo(buttonView, 'execute', () => this.editor.execute('splitParagraph'));

      return buttonView;
    });
    // Add toolbar button.
    this.editor.commands.add(
      'splitParagraph',
      new SplitParagraphCommand(this.editor),
    );
  }

  afterInit() {
    // Set value of the new paragraph.
    if (window._splitParagraph) {
      if (typeof window._splitParagraph.data.second === 'string') {
        const paragraph = this.editor.sourceElement.closest('tr.draggable');
        let previousParagraph = paragraph?.previousElementSibling;

        while (previousParagraph) {
          if (previousParagraph.matches("tr.draggable")) break;
          previousParagraph = previousParagraph.previousElementSibling;
        }

        if (
          [...paragraph.parentElement.children].indexOf(previousParagraph) === window._splitParagraph.originalRowIndex &&
          this.editor.sourceElement.dataset.drupalSelector.match(window._splitParagraph.selector.replace(/-[0-9]+-?/, '-[0-9]+-'))) {
          // Defer to wait until init is complete.
          setTimeout(() => {
            this.editor.setData(window._splitParagraph.data.second);
            window._splitParagraph.data.second = null;
          }, 0);
        }
      }

      if (typeof window._splitParagraph.data.first === 'string') {
        if (this.editor.sourceElement.dataset.drupalSelector === window._splitParagraph.selector) {
          // Defer to wait until init is complete.
          setTimeout(() => {
            this.editor.setData(window._splitParagraph.data.first);
            window._splitParagraph.data.first = null;
          }, 0);
        }
      }
    }
  }
}

export default {
  SplitParagraph,
};
