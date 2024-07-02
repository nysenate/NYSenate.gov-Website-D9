# Views Combine

A field plugin for views to combine additional views with UNION queries.

> **WARNING:** This module is intended for advanced site builders. It's implementation must have careful consideration.

## Prerequisites

1. Base view and combined views must use the unformatted list display style.
2. Base view must use fields row style.
3. Combined views may use content or fields row style.

## Usage

### Configuration

1. Edit the compatible base view display.
2. Add new "Global: Views combine" field to the display.
3. Choose the compatible view to combine and apply.
4. (optional) Set "Tag based (views combined)" caching strategy.
5. (optional) Repeat 1-3 for each combined view.

### Exposed filters

Exposed filters are inherited from the top down. Meaning exposed input values from the base view are actively passed
down to each combined view. Exposed input is only captured from the base view. With a couple important caveats**:

1. Base view and all combined views must be configured with the exposed filter.
2. Exposed filter identifier must be identical in the base view and all combined views.

** With exception to "Global: Views combine", a special filter to include or exclude results from the base
view, any combined view, or a combination of both. It's recommended to only use this as an exposed filter. Otherwise,
the same result can be achieved by simply adding or removing "Global: Views combine" fields.

### Non-exposed and exposed sorts

Similar to exposed filters, exposed sorts are inherited from the top down. Great news! This module attempts to
normalize sort fields in each query to support robust configurations. What's that mean? Any field
used to sort in the base view or combined views is tacked on using an `order_#` alias. Where `#` is the order fields
exact position in the relevant query. This isn't perfect by any means. Site builders are responsible for structuring
view sorts in a way that makes sense for the intended results. Rudimentary example:

**Views (Base) Query A:**
```sql
SELECT users_field_data.uid AS uid
FROM users_field_data
ORDER BY users_field_data.created DESC
```

**Views Query B:**
```sql
SELECT comment_field_data.cid AS cid
FROM comment_field_data
ORDER BY comment_field_data.created DESC, comment_field_data.changed DESC
```

**Views Combined Query (A+B):**
```sql
SELECT users_field_data.uid AS uid, NULL AS cid, users_field_data.created AS order_1, NULL AS order_2
FROM users_field_data
UNION
SELECT NULL as uid, comment_field_data.cid AS cid, comment_field_data.created AS order_1, comment_field_data.changed AS order_2
FROM comment_field_data
ORDER BY order_1 DESC, order_2 DESC
```

> **NOTE**: Use `hook_views_query_alter()` for advanced exposed sort implementations.

## Caching

> **IMPORTANT:** Set caching to "Tag based (views combined)" on views combining one or more views.

Use "Tag based (views combined)" caching to ensure view configuration and entity list cache tags are merged. Entity
result cache tags are merged in both "Tag based" and "Tag based (views combined)" caching strategies.

## Limitations

### Display style

> See `views_combine_views_plugins_style_alter()` for out-of-box supported display styles.

Limited support. Additional display styles could be supported by overriding the views style plugin class with a new
class which extends the original class and uses the `CombineStyleTrait` trait. See
`views_combine_views_plugins_style_alter()` and `\Drupal\views_combine\Plugin\style\DefaultStyle.php` for an example.
However, keep in mind certain advanced display styles do more under the hood and likely require new traits or methods.
In worst case scenarios, entirely new solutions.

### Fields

Combined views only render fields also included in the base view. For example, if the base view uses the
rendered entity field, each combined view should use the same rendered entity field. Support for rendering of view
specific fields is planned.

### Configuration errors

*Be careful!* This module has very limited built-in configuration validation.
