import { expect, type TestInfo } from '@playwright/test';
import type { AxeResults } from 'axe-core';

export const wcagTags = [
  'wcag2a',
  'wcag2aa',
  'wcag21a',
  'wcag21aa',
  'wcag22a',
  'wcag22aa',
];

export async function assertNoAxeViolations(
  results: AxeResults,
  testInfo: TestInfo,
): Promise<void> {
  await testInfo.attach('axe-results', {
    body: JSON.stringify(results, null, 2),
    contentType: 'application/json',
  });

  const summary = results.violations.map(
    ({ id, impact, nodes }) => `${id} (${impact ?? 'unknown'}, ${nodes.length} node(s))`,
  );

  expect(
    results.violations.length,
    [
      `Axe found ${results.violations.length} violation rule(s):`,
      ...summary,
      'See the axe-results attachment for details.',
    ].join('\n'),
  ).toBe(0);
}
