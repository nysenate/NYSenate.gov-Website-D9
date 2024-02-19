const { defineConfig } = require('cypress')
module.exports = defineConfig({
  video: false,
  viewportWidth: 1280,
  viewportHeight: 720,
  e2e: {
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
    baseUrl: 'https://nysenate.ddev.site'
  },
})
