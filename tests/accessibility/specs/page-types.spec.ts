import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { routes } from '../fixtures/routes';
import {
  assertNoAxeViolations,
  wcagTags,
} from '../helpers/accessibility';

for (const route of routes) {
  test(`${route.name} has no accessibility violations`, async ({ page }, testInfo) => {
    const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });

    expect(response, `${route.path} did not return a response`).not.toBeNull();
    expect(response?.status(), `${route.path} returned an error status`).toBeLessThan(400);

    const axeBuilder = new AxeBuilder({ page }).withTags(wcagTags);
    if ('axeExclusions' in route) {
      for (const selector of route.axeExclusions) {
        axeBuilder.exclude(selector);
      }
    }
    const results = await axeBuilder.analyze();

    await assertNoAxeViolations(results, testInfo);
  });
}

