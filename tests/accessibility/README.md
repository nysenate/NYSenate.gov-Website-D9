# Accessibility tests

Playwright and Axe test representative rendered Drupal pages for WCAG 2.0, 2.1,
and 2.2 Level A and AA violations. Every test is a strict pass/fail check for
in-scope content. The About route excludes only the third-party YouTube oEmbed
iframe from Axe because its internal player markup is vendor-controlled; a
separate test asserts that the iframe is visible and has a descriptive title.
There is no baseline or violation threshold. The suite runs automatically only
in pull request CI after the PR's Pantheon multidev has been deployed.

Existing violations will fail CI until fixed. This is intentional: the gate
reports current accessibility debt directly rather than treating it as accepted.

## Local troubleshooting

Automatic execution is CI-only. A failed CI run can be reproduced manually:

```bash
nvm use
npm ci
npx playwright install chromium
BASE_URL=https://pr-NNN-nysenate-2022.pantheonsite.io \
PANTHEON_TEST_UA='<allowlisted user agent>' \
npm test
```

The HTML report is written to `playwright-report/`. Full Axe results, screenshots,
and Playwright traces are attached to individual tests. In CI, GitHub Actions
uploads these files as the `accessibility-report` workflow artifact, downloadable
from that workflow run for 30 days; they are not sent to Pantheon or a separate
reporting service.
