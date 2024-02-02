const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://mcrain.ddev.site',
    // Allows for use of cy.task('log', 'This will be output to the terminal')
    // for console logging and debugging.
    setupNodeEvents(on, config) {
      on('task', {
        log(message) {
          console.log(message)

          return null
        },
      })
    },
  },
  env: {
    'drushCommand': 'ddev drush',
    grepFilterSpecs: true,
    grepOmitFiltered: true
  },
  setupNodeEvents(on, config) {
    require('@cypress/grep/src/plugin')(config);
    return config;
  },
  video: false
});
