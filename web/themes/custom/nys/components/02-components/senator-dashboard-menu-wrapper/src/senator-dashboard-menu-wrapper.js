/* eslint-disable */
((Drupal) => {
  Drupal.behaviors.nysDashboard = {
    dashboardSelector: '.c-senator-dashboard-menu-wrapper',
    triggerSelector: '.c-senator-dashboard-menu-wrapper__trigger',

    getTarget: function (trigger) {
      const triggerId = trigger.getAttribute('aria-controls');
      return document.getElementById(triggerId);
    },
    isOpen: function (trigger) {
      return trigger.getAttribute('aria-expanded') === 'true';
    },
    menuOpen: function (trigger) {
      const target = this.getTarget(trigger);

      trigger.setAttribute('aria-expanded', 'true');
      target.setAttribute('aria-hidden', 'false');
    },
    menuClose: function (trigger) {
      const target = this.getTarget(trigger);

      trigger.setAttribute('aria-expanded', 'false');
      target.setAttribute('aria-hidden', 'true');
    },
    handleTriggerClick: function (event) {
      const trigger = event.target.closest(this.triggerSelector);
      this.isOpen(trigger) ? this.menuClose(trigger) : this.menuOpen(trigger);
    },
    bodyClose: function (event) {
      if (!event.target.closest(this.dashboardSelector)) this.menuClose();
    },
    init: function (dashboard) {
      const triggers = dashboard.querySelectorAll(this.triggerSelector);
      triggers.forEach(trigger => trigger.addEventListener('click', this.handleTriggerClick.bind(this)));
      document.body.classList.add('nysenate-dashboard-init');
      document.body.addEventListener('click', this.bodyClose.bind(this));

      // Check if a div with class c-actionbar or hero--senator exists on the page and doesn't have the hidden class
      const actionbar = document.querySelector('.c-actionbar');
      const heroSenator = document.querySelector('.hero--senator');

      // Apply padding-inline: 0 if either element exists and is not hidden
      if ((actionbar && !actionbar.classList.contains('hidden')) || 
          (heroSenator && !heroSenator.classList.contains('hidden'))) {
        dashboard.style.paddingInline = '0';
      }
    },
    attach: function (context) {
      const dashboards = once('senateDashboard', this.dashboardSelector, context);
      dashboards.forEach(dashboard => this.init(dashboard));
    },
  };
})(Drupal);
