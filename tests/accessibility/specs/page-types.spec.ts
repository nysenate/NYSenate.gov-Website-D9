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

    const results = await new AxeBuilder({ page }).withTags(wcagTags).analyze();

    await assertNoAxeViolations(results, testInfo);
  });
}

