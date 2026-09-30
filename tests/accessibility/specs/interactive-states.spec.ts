import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import {
  assertNoAxeViolations,
  wcagTags,
} from '../helpers/accessibility';

test('opened site search has no accessibility violations', async ({ page }, testInfo) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  await page.getByRole('button', { name: 'open and focus search' }).click();

  const searchInput = page.getByRole('textbox', { name: 'Search Term' });
  await expect(searchInput).toBeVisible();
  await expect(searchInput).toBeFocused();

  const results = await new AxeBuilder({ page }).withTags(wcagTags).analyze();

  await assertNoAxeViolations(results, testInfo);
});
