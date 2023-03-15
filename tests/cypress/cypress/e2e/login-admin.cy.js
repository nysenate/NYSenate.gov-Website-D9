import admin from '../fixtures/users/role-admin.json'
import content from '../fixtures/content/node-landing-page.json'

let adminData = {};

describe('Log in as admin and perform actions', () => {
  before(() => {
    cy.createUserWithRole(admin.username, admin.mail, admin.roles).then((userData) => {
      // Store user data with uid so we can pass that to the session.
      adminData = userData;
    });
  });

  beforeEach(() => {
    // Login as admin user.
    cy.loginWithEmail(admin.mail, adminData.uid);
    cy.visit('/node/add/landing')
  });

  it('create landing page', () => {
    // Create the Landing Page node.
    cy.get('[data-drupal-selector="edit-title-0-value"]').type(content['page-title'])

    // Add a text component.
    cy.get('[data-drupal-selector="edit-field-landing-blocks-actions-bundle"]').select('Quote')
    cy.get('[data-drupal-selector="edit-field-landing-blocks-actions-ief-add"]').first().click();
    cy.get('[data-drupal-selector="edit-field-landing-blocks-form-0-field-author-0-value"]').type(content['component-quote-author'])
    cy.get('[data-drupal-selector="edit-field-landing-blocks-form-0-field-quote-0-value"]').type(content['component-quote-quote'])
    cy.get('[data-drupal-selector="edit-field-landing-blocks-form-0-actions-ief-add-save"]').first().click();

    // Save the node.
    cy.get('[data-drupal-selector="edit-submit"]:first').click();

    // Verify node was created and content appears on a page.
    cy.get('h1').should('contain', content['page-title'])
    cy.get('div.quote__cite-text').should('contain', content['component-quote-author'])
    cy.get('p.quote__text').should('contain', content['component-quote-quote'])
  })
});
