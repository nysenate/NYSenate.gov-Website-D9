/**
 * Closes any open bill status dropdown (native <details>) when the user
 * clicks elsewhere on the page. The `name="bill-milestones"` attribute on
 * the <details> elements (nys-bill-milestones.html.twig) already makes
 * them mutually exclusive - opening one auto-closes any other open one.
 * This only handles closing on an outside click, which that grouping
 * doesn't cover.
 */
document.addEventListener('click', (event) => {
  document.querySelectorAll('details.c-bill-milestones[open]').forEach((details) => {
    if (!details.contains(event.target)) {
      details.removeAttribute('open');
    }
  });
});
