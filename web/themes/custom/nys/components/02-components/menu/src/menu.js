/* eslint-disable */
((Drupal) => {
  Drupal.behaviors.menuControl = {
    menuSelector: '.c-menu__items',
    triggerSelector: 'button.c-menu__link',
    linkSelector: 'a.c-menu__link',
    menu: null,
    allItems: [],
    triggers: [],

    getTarget: function (trigger) {
      const triggerId = trigger.getAttribute('aria-controls');
      return document.getElementById(triggerId);
    },
    getDepth: function (item) {
      return item.dataset.menuDepth;
    },
    getItem: function (items, activeItem, direction = 'before') {
      const depth = this.getDepth(activeItem);
      const activeIndex = items.findIndex(item => {
        if (depth > 0 && item.below.length > 0) {
          const nestedItemIndex = this.getItem(item.below, activeItem, direction);
          if (nestedItemIndex > -1) return true;
        }

        if (item.item.isSameNode(activeItem)) return true;
        return false;
      });

      if (activeIndex === 0 && direction === 'before') return null;
      if (activeIndex + 1 === items.length && direction === 'after') return null;
      if (direction === 'before') return items[activeIndex - 1];
      if (direction === 'after') return items[activeIndex + 1];
      if (direction === 'current') return items[activeIndex];

      return null;
    },
    handleTriggerClick: function (event) {
      const trigger = event.target.closest(this.triggerSelector);
      this.isOpen(trigger) ? this.menuClose(trigger) : this.menuOpen(trigger);
    },
    handleKeypress: function (event) {
      if (event.ctrlKey || event.altKey || event.metaKey) return;

      switch (event.key) {
        case 'Up':
        case 'ArrowUp':
          this.moveUp(event.target);
          event.preventDefault();
          break;
        case 'Down':
        case 'ArrowDown':
          this.moveDown(event.target);
          event.preventDefault();
          break;
        case 'Left':
        case 'ArrowLeft':
          this.moveLeft(event.target);
          event.preventDefault();
          break;
        case 'Right':
        case 'ArrowRight':
          this.moveRight(event.target);
          event.preventDefault();
          break;
        case 'Escape':
        case 'Esc':
          const target = event.target.closest(this.menuSelector);
          const triggerId = target.getAttribute('id');
          const trigger = document.querySelector(`[aria-controls='${triggerId}']`);

          trigger.focus();
          this.menuClose(trigger);
          event.preventDefault();
          break;
      }
    },
    isOpen: function (trigger) {
      return trigger.getAttribute('aria-expanded') === 'true';
    },
    moveUp: function (activeItem) {},
    moveDown: function (activeItem) {
      const depth = this.getDepth(activeItem);
      const isToggle = activeItem.hasAttribute('aria-expanded');

      if (depth === '0' && isToggle) {
        const newItem = this.getItem(this.allItems, activeItem, 'current');

        if (newItem !== null && newItem.below.length > 0) {
          this.menuOpen(activeItem);
          newItem.below[0].item.focus();
        }
      }
    },
    moveLeft: function (activeItem) {
      const newItem = this.getItem(this.allItems, activeItem, 'before');

      if (newItem !== null) {
        if (activeItem.hasAttribute('aria-expanded')) this.menuCloseAll();
        newItem.item.focus();
      }
    },
    moveRight: function (activeItem) {
      const newItem = this.getItem(this.allItems, activeItem, 'after');

      if (newItem !== null) {
        if (activeItem.hasAttribute('aria-expanded')) this.menuCloseAll();
        newItem.item.focus();
      }
    },
    menuOpen: function (trigger) {
      const target = this.getTarget(trigger);

      this.menuCloseAll();
      trigger.setAttribute('aria-expanded', 'true');
      target.setAttribute('aria-hidden', 'false');
    },
    menuClose: function (trigger) {
      const target = this.getTarget(trigger);

      trigger.setAttribute('aria-expanded', 'false');
      target.setAttribute('aria-hidden', 'true');
    },
    menuCloseAll: function () {
      this.triggers.forEach(trigger => this.menuClose(trigger));
    },
    bodyClose: function (event) {
      if (!event.target.closest(this.menuSelector)) this.menuCloseAll();
    },
    initAllItems: function (items) {
      items.forEach(item => {
        const parentMenu = item.closest(this.menuSelector);
        const depth = this.getDepth(parentMenu);

        if (depth == '1') {
          const targetId = parentMenu.getAttribute('id');
          const trigger = document.querySelector(`[aria-controls="${targetId}"]`);
          const parentIndex = this.allItems.findIndex(element => element.item.isSameNode(trigger));

          this.allItems[parentIndex].below.push({item, depth, below: []});
        }
        else {
          this.allItems.push({item, depth, below: []});
        }
        item.addEventListener('keydown', this.handleKeypress.bind(this));
      });
    },
    initChildLinks: function (trigger) {
      const target = this.getTarget(trigger);
      const childLinks = target.querySelectorAll(this.linkSelector);

      childLinks.forEach(link => link.addEventListener('keydown', this.handleKeypress.bind(this)));
    },
    initTrigger: function (trigger) {
      this.triggers.push(trigger);
      this.initChildLinks(trigger);
      trigger.addEventListener('click', this.handleTriggerClick.bind(this));
    },
    init: function (menu) {
      const triggers = menu.querySelectorAll(this.triggerSelector);
      const menuItems = menu.querySelectorAll(`${this.triggerSelector}, ${this.linkSelector}`);

      triggers.forEach(trigger => this.initTrigger(trigger));
      this.initAllItems(menuItems);
      this.menu = menu;

      document.body.addEventListener('click', this.bodyClose.bind(this));
    },
    attach: function (context) {
      const menus = once('nysMenu', '.c-menu', context);
      menus.forEach(menu => this.init(menu));
    },
  };
})(Drupal);
