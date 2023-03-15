describe('Verify home page loads', () => {
  beforeEach(() => cy.visit('/'))

  it('home page loads', () => {
    cy.title().should('contains', 'NYSenate.gov')
  })
})
