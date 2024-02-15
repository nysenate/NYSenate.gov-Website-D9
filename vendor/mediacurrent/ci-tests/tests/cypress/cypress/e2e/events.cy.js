describe('Verify event page loads', () => {
  beforeEach(() => cy.visit('/layout/events'))

  it('event page loads', { tags: ['@smoke', '@events'] }, () => {
    cy.title().should('contains', 'Events')
  })
})
