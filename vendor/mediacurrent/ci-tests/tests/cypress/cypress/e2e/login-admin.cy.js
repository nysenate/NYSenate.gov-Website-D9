import admin from '../fixtures/users/role-admin.json'
import content from '../fixtures/content/node-basic-page.json'

let adminData = {};

describe('Login as admin and perform actions', { tags: ['@local', '@login-admin'] }, () => {
  before(() => {
    cy.createUserWithRole(admin.username, admin.mail, admin.roles).then((userData) => {
      // Store user data with uid so we can pass that to the session.
      adminData = userData;
    });
  });

  beforeEach(() => {
    // Login as admin user.
    cy.loginWithEmail(admin.mail, adminData.uid);
    cy.visit('/node/add/page')
  });

  it('create basic page', () => {
    // Create the basic page node.
    cy.get('[data-drupal-selector="edit-title-0-value"]').type(content['title'])
    cy.type_ckeditor("edit-body-0-value", content['body']);
    cy.get('[data-drupal-selector="edit-submit"]').first().click();

    // Verify node was created and content appears on a page.
    cy.get('h1').should('contain', content['title'])
    cy.get('.node__body').should('contain.html', content['body']);
  })
});
