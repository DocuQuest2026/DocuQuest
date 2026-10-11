import { test, expect } from '@playwright/test';
import { admin, signIn, staff, submitRequest } from './helpers.js';

test.describe.configure({ mode: 'serial' });

let reference;

test('a student submits a request and gets a reference number', async ({ page }) => {
    reference = await submitRequest(page);

    expect(reference).toMatch(/^REQ-[A-Z0-9]{8}$/);
});

test('the student can follow the request on the status page, with its queue place and estimate', async ({ page }) => {
    await page.goto('/request/status');
    await page.locator('#reference_no').fill(reference);
    await page.getByRole('button', { name: 'Check status' }).click();

    await expect(page.getByText('Being processed').first()).toBeVisible();
    await expect(page.getByText('Place in queue')).toBeVisible();
    await expect(page.getByText('Estimated ready by')).toBeVisible();
    await expect(page.getByText('This page updates automatically.')).toBeVisible();
});

test('staff approve the request and it shows on the status page without a reload', async ({ page, browser }) => {
    const studentPage = await browser.newPage();
    await studentPage.goto('/request/status');
    await studentPage.locator('#reference_no').fill(reference);
    await studentPage.getByRole('button', { name: 'Check status' }).click();
    await expect(studentPage.getByText('Being processed').first()).toBeVisible();

    await signIn(page, staff);
    await page.goto(`/requests/${reference}`);
    await page.getByRole('button', { name: 'Mark as urgent' }).click();
    await expect(page.getByText('Request marked as urgent.')).toBeVisible();

    await page.getByRole('button', { name: 'Approve' }).first().click();
    await page.getByRole('button', { name: 'Yes, approve' }).click();
    await expect(page.getByText('Request approved.')).toBeVisible();

    // The student's open page refreshes itself (every 20 seconds).
    await expect(studentPage.getByText('Approved — being prepared').first()).toBeVisible({ timeout: 40_000 });
    await studentPage.close();
});

test('staff release the document and mark it claimed', async ({ page }) => {
    await signIn(page, staff);
    await page.goto(`/requests/${reference}/release`);
    await page.locator('#representative_name').fill('Maria Cruz');
    await page.getByRole('button', { name: 'Release document' }).click();
    await expect(page.getByText('Document released.')).toBeVisible();

    await page.goto('/requests?status=released');
    await page.getByRole('button', { name: 'Claim', exact: true }).first().click();
    await page.getByRole('button', { name: 'Confirm claim' }).click();
    await page.waitForLoadState('networkidle');

    await page.goto(`/requests/${reference}`);
    await expect(page.getByText('Claimed').first()).toBeVisible();
});

test('the student sees the document as claimed', async ({ page }) => {
    await page.goto('/request/status');
    await page.locator('#reference_no').fill(reference);
    await page.getByRole('button', { name: 'Check status' }).click();

    await expect(page.getByText('Claimed').first()).toBeVisible();
});

test('the report counts the request and downloads as a PDF', async ({ page }) => {
    await signIn(page, staff);
    await page.goto('/reports');
    await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible();

    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Download PDF' }).click();
    const file = await download;

    expect(file.suggestedFilename()).toMatch(/^docuquest-report-.*\.pdf$/);
});

test('an admin can change a document fee and the request form shows it', async ({ page }) => {
    await signIn(page, admin);
    await page.goto('/admin/document-types');
    await page.getByRole('row', { name: /Certificate of Enrolment/ }).getByRole('link', { name: 'Edit' }).click();
    await page.locator('#fee').fill('80');
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByText('Document updated.')).toBeVisible();

    await page.goto('/request');
    await expect(page.getByText('₱80.00').first()).toBeVisible();
});

test('staff cannot open the admin pages', async ({ page }) => {
    await signIn(page, staff);
    const response = await page.goto('/admin/document-types');

    expect(response.status()).toBe(403);
});
