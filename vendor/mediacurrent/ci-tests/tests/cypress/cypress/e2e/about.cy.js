describe('Verify about page loads', () => {
  beforeEach(() => cy.visit('/layout/about'))

  it('about page loads', { tags: ['@smoke', '@about'] }, () => {
    cy.title().should('contains', 'About')
  })
})
