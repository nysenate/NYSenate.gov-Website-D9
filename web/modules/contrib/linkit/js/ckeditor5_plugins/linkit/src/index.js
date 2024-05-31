import { Plugin } from 'ckeditor5/src/core';
import LinkitEditing from './linkitediting';
import initializeAutocomplete from './autocomplete';

class Linkit extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [LinkitEditing];
  }

  init() {
    this._state = {};
    const editor = this.editor;
    const options = editor.config.get('linkit');
    this._enableLinkAutocomplete();
    this._handleExtraFormFieldSubmit();
    this._handleDataLoadingIntoExtraFormField();
  }

  _enableLinkAutocomplete() {
    const editor = this.editor;
    const options = editor.config.get('linkit');
    const linkFormView = editor.plugins.get( 'LinkUI' ).formView;
    let wasAutocompleteAdded = false;

    linkFormView.extendTemplate( {
      attributes: {
        class: ['ck-vertical-form', 'ck-link-form_layout-vertical']
      }
    } );

    editor.plugins.get( 'ContextualBalloon' )._rotatorView.content.on('add', ( evt, view ) => {
      if ( view !== linkFormView || wasAutocompleteAdded ) {
        return;
      }

      /**
       * Used to know if a selection was made from the autocomplete results.
       *
       * @type {boolean}
       */
      let selected;

      initializeAutocomplete(
        linkFormView.urlInputView.fieldView.element,
        {
          ...options,
          selectHandler: (event, { item }) => {
            if (!item.path) {
              throw 'Missing path param.' + JSON.stringify(item);
            }

            if (item.entity_type_id || item.entity_uuid || item.substitution_id) {
              if (!item.entity_type_id || !item.entity_uuid || !item.substitution_id) {
                throw 'Missing path param.' + JSON.stringify(item);
              }

              this.set('entityType', item.entity_type_id);
              this.set('entityUuid', item.entity_uuid);
              this.set('entitySubstitution', item.substitution_id);
            }
            else {
              this.set('entityType', null);
              this.set('entityUuid', null);
              this.set('entitySubstitution', null);
            }

            event.target.value = item.path;
            selected = true;
            return false;
          },
          openHandler: (event) => {
            selected = false;
          },
          closeHandler: (event) => {
            if (!selected) {
              this.set('entityType', null);
              this.set('entityUuid', null);
              this.set('entitySubstitution', null);
            }
            selected = false;
          },
        },
      );

      wasAutocompleteAdded = true;
      linkFormView.urlInputView.fieldView.template.attributes.class.push('form-linkit-autocomplete');
    });
  }

  _handleExtraFormFieldSubmit() {
    const editor = this.editor;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');

    this.listenTo(linkFormView, 'submit', () => {
      const values = {
        'data-entity-type': this.entityType,
        'data-entity-uuid': this.entityUuid,
        'data-entity-substitution': this.entitySubstitution,
      }
      // Stop the execution of the link command caused by closing the form.
      // Inject the extra attribute value. The highest priority listener here
      // injects the argument (here below 👇).
      // - The high priority listener in
      //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets
      //   the extra attribute.
      // - The normal (default) priority listener in ckeditor5-link sets
      //   (creates) the actual link.
      linkCommand.once('execute', (evt, args) => {
        if (args.length < 3) {
          args.push(values);
        } else if (args.length === 3) {
          Object.assign(args[2], values);
        } else {
          throw Error('The link command has more than 3 arguments.')
        }
      }, { priority: 'highest' });
    }, { priority: 'high' });
  }

  _handleDataLoadingIntoExtraFormField() {
    const editor = this.editor;
    const linkCommand = editor.commands.get('link');

    this.bind('entityType').to(linkCommand, 'data-entity-type');
    this.bind('entityUuid').to(linkCommand, 'data-entity-uuid');
    this.bind('entitySubstitution').to(linkCommand, 'data-entity-substitution');
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'Linkit';
  }
}

export default {
  Linkit,
};
