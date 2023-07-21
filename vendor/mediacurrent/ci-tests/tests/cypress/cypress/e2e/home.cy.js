describe('Verify home page loads', () => {
  beforeEach(() => cy.visit('/'))

  it('home page loads', { tags: ['@smoke', '@p1', '@home]'] }, () => {
    cy.title().should('contains', 'Introducing Rain')
  })
})
