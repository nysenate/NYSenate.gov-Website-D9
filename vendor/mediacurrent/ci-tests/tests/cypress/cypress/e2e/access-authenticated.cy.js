import authUser from '../fixtures/users/role-authenticated.json';

let authUserData = {};

describe('Authenticated User Access', { tags: ['@local', '@access-authenticated'] }, () => {
  before(() => {
    cy.createUserWithRole(authUser.username, authUser.mail).then((userData) => {
      // Store user data with uid so we can pass that to the session.
      authUserData = userData;
    });
  });

  beforeEach(() => {
    // Login as user.
    cy.loginWithEmail(authUser.mail, authUserData.uid);
  });

  // Define 403 test cases.
  const testCases = {
    'Cannot access content page': '/admin/content',
    'Cannot access other user page': '/user/1',
  }

  Object.keys(testCases).forEach((title) => {
    // Loop through all test cases to ensure Members cannot access
    // admin or other member pages.
    it(title, () => {
      cy.request({
        url: testCases[title],
        followRedirect: false,
        failOnStatusCode: false,
      }).then((resp) => {
        expect(resp.status).to.eq(403);
        expect(resp.redirectedToUrl).to.eq(undefined);
      });
    })
  });
});
