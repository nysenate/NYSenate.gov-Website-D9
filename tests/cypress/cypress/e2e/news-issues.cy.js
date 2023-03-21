describe('Verify News & Issues page loads', () => {
  beforeEach(() => cy.visit('/news-and-issues'))

  it('about page loads', () => {
    cy.title().should('contains', 'News from the Majority')
  })
})
