INTRODUCTION
------------

Twig Field Value allows Drupal themers to print field labels and field values
individually. It provides two Twig filters one that print a field label and
one that prints field value(s).

Filters:
- field_label :         Returns the field label value.
- field_value :         Returns the render array of the field value(s) without
                        the field wrappers.
- field_raw :           Returns raw field properties value(s).
- field_target_entity : Returns the referenced entity object(s) of an entity
                        reference field.

USAGE
-----

To print the label and value of a field:
  <strong>{{ content.field_name|field_label }}</strong>: {{ content.field_name|field_value }}

To print the label and values of a field with multiple values:
  <strong>{{ content.field_name|field_label }}</strong>: {{ content.field_name|field_value|safe_join(', ') }}

To print image link and the alt text of an image:
  <img src={{ file_url(content.field_image|field_target_entity.uri.value) }} alt={{ content.field_image|field_raw('alt') }} />

The above examples assume that 'content.field_example' is the render array of
the of a field, as for example in a node template.

AUTHOR
------
Erik Stielstra (Sutharsan, https://www.drupal.org/u/sutharsan)
