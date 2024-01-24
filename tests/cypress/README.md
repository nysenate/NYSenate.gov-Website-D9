# Cypress Testing

As of Jan 2024, the Visual Regression Testing testing is the main
test being used. The E2E tests are still available, and will be
built upon in the future.

## Cypress Setup
Update `./tests/cypress/cypress.config.js` for your environment. For the
most part, you'll just need to update your `.env` file.

Change to the Cypress test directory:
```
cd ./tests/cypress
```

Set up the Node environment and install Cypress:
```
nvm use
nvm install
npm install
```

## Cypress E2E Testing

Runs Cypress tests from the CLI without the GUI:
```
npx cypress run
```

Opens Cypress in the interactive GUI:
```
npx cypress open
```
> **_NOTE:_** Run `npx cypress help` for more information.

## Cypress Visual Regression Testing

NYS leverages Percy for visual regression testing. Percy is a visual testing
platform that allows us to compare screenshots of our application to a baseline.
This allows us to catch visual regressions before they are deployed to prod.

### Adding a new url to visual regression testing

Open `tests/cypress/cypress/fixtures/percy_vrt_urls.json` and add the url.

### Running visual regression tests locally

1. Ensure that you have a `.env` file. See `.env.example` for an example.
2. Update relevant environment variables in `.env`
3. `npx percy exec -- cypress run --config baseUrl=https://www.nysenate.gov --spec "cypress/e2e/visual-regression.cy.js"`

Setting the `PERCY_BRANCH` to `main` will refresh the baseline images.

Any other branch name will compare against the baseline images.

### Running visual regression tests with Github Actions

1. Navigate to the "Actions" tab in Github for this repo.
2. Click on the "Visual Regression Testing" workflow.
3. Click on the "Run workflow" button.
4. Enter the URL that you wish to test. See more information below.

#### Updating the baseline

Setting to https://www.nysenate.gov will refresh the baseline images.

Any other URL will compare against the baseline images.
