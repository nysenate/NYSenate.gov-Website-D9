### Library seven/global_styling overrides
Below listed files actually override the seven/global_styling libraray. Files not listed here merely import seven styles. 

After https://drupal.org/i/2642122 has landed we can specifically override the files listed here.

<pre>
  seven/global-styling:
    css:
      base:
        css/base/elements.css: css/base/elements.css
      component:
        css/components/admin-list.css: css/components/admin-list.css
        css/components/content-header.css: css/components/content-header.css
        css/components/breadcrumb.css: css/components/breadcrumb.css
        css/components/buttons.css: css/components/buttons.css
        css/components/messages.css: css/components/messages.css
        css/components/dropbutton.component.css: css/components/dropbutton.component.css
        css/components/entity-meta.css: css/components/entity-meta.css
        css/components/form.css: css/components/form.css
        css/components/menus-and-lists.css: css/components/menus-and-lists.css
        css/components/tablesort-indicator.css: css/components/tablesort-indicator.css
        css/components/system-status-report.css: css/components/system-status-report.css
        css/components/tabs.css: css/components/tabs.css
</pre>
