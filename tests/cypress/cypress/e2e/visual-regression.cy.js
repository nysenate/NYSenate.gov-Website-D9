// You can add new URLs to this fixture.
import vrtUrls from '../fixtures/percy_vrt_urls.json'

Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test
  return false
})

describe('Bulk Visual Regression testing with Percy', () => {
  it('Sending all URLs to Percy', function () {
    vrtUrls.forEach((data) => {
      cy.visit(data.path)
      cy.scrollTo('bottom')
      cy.percySnapshot(data.name)
    })
  })
})
