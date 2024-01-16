# Visual Regression Testing

NYS leverages Percy for visual regression testing. Percy is a visual testing platform that allows us to compare screenshots of our application to a baseline. This allows us to catch visual regressions before they are deployed to production.

## Adding a new url to visual regression testing

Open `tests/visual-regression/snapshots.yml` and add the url.

## Running visual regression tests locally

1. cd `tests/visual-regression`
2. `nvm use`
3. `npm install`
4. Ensure that you have a `.env` file. See `.env.example` for an example.
5. Update relevant environment variables in `.env`
6. `npx percy snapshot snapshots.yml --base-url=https://pr-105-nysenate-2022.pantheonsite.io`

Setting the `PERCY_BRANCH` to `main` will refresh the baseline images.

Any other branch name will compare against the baseline images.

## Running visual regression tests with Github Actions

1. Navigate to the "Actions" tab in Github for this repo.
2. Click on the "Visual Regression Testing" workflow.
3. Click on the "Run workflow" button.
4. Enter the URL that you wish to test. See more information below.

### Updating the baseline

Setting to https://www.nysenate.gov will refresh the baseline images.

Any other URL will compare against the baseline images.
