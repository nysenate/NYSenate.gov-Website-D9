# Rain Admin Field Label
This module provides a third party field widget setting that hides the label of
the field on the edit page. This is useful when you are relying on a field group
to provide field titles.

For accessibility purposes, the title is still rendered, but is visually hidden
using CSS.

## Usage
* Install module by running
```
drush en -y rain_admin_field_label
```
* Navigate to Content Types > Your content type > Manage Form.
* Choose a field and click the settings icon on the right
* Find the "Hide input label" checkbox and toggle it.