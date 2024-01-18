// You can add new URLs to this fixture.
import vrtUrls from '../fixtures/percy_vrt_urls.json'

Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test
  return false
})

describe('Bulk Visual Regression testing with Percy', () => {
  vrtUrls.forEach((data) => {
    it(`Sending ${data.name} (${data.path}) to Percy`, function () {
      cy.visit(data.path)
      cy.get('.front-end').should('be.visible');
      cy.scrollTo('bottom')
      cy.percySnapshot(data.name)
    })
  })
})
