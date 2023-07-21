describe('Verify blog page loads', () => {
  beforeEach(() => cy.visit('/layout/blog'))

  it('blog page loads', { tags: ['@smoke', '@blog'] }, () => {
    cy.title().should('contains', 'Blog')
  })
})
