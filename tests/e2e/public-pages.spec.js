import { test, expect } from '@playwright/test';

test('the landing page offers every document and a request button', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('link', { name: 'Request Now' }).first()).toBeVisible();
    await expect(page.getByText('Transcript of Records').first()).toBeVisible();
    await expect(page.getByText('₱150.00 per copy')).toBeVisible();
});

test('the request form lists the documents with fees and shows the estimate once one is chosen', async ({ page }) => {
    await page.goto('/request');

    await expect(page.getByText('Certificate of Enrolment').first()).toBeVisible();
    await expect(page.getByText('No document chosen yet.')).toBeVisible();

    await page.locator('#document_transcript_of_records').check();

    await expect(page.getByText('Estimated ready by').first()).toBeVisible();
    await expect(page.getByText('₱150.00').first()).toBeVisible();
});

test('submitting an empty request form is stopped by validation', async ({ page }) => {
    await page.goto('/request');
    await page.getByRole('button', { name: 'Submit request' }).click();

    await expect(page).toHaveURL(/\/request$/);
    await expect(page.getByRole('button', { name: 'Confirm and submit' })).toHaveCount(0);
});

test('the status page rejects an unknown reference number', async ({ page }) => {
    await page.goto('/request/status');
    await page.locator('#reference_no').fill('REQ-NOTREAL1');
    await page.getByRole('button', { name: 'Check status' }).click();

    await expect(page.getByText('No request was found for that reference number.')).toBeVisible();
});

test('staff pages require signing in', async ({ page }) => {
    await page.goto('/requests');

    await expect(page).toHaveURL(/staff\/login/);
});
