export const staff = { email: 'e2e.staff@gmail.com', password: 'password' };
export const admin = { email: 'e2e.admin@gmail.com', password: 'password' };

export async function signIn(page, account) {
    await page.goto('/staff/login');
    await page.locator('#email').fill(account.email);
    await page.locator('input[name="password"]').fill(account.password);
    await page.getByRole('button', { name: /sign in|log in/i }).click();
    await page.waitForURL('**/dashboard');
}

/**
 * Fill in and submit the public request form for one document, and return the reference number.
 */
export async function submitRequest(page, { documentKey = 'transcript_of_records', studentNo = '2026-00001', first = 'Maria', last = 'Cruz', email = 'e2e.student@gmail.com' } = {}) {
    await page.goto('/request');
    await page.locator('#student_no').fill(studentNo);
    await page.locator('#first_name').fill(first);
    await page.locator('#last_name').fill(last);
    await page.locator('#course').selectOption({ index: 1 });
    await page.locator('#email').fill(email);
    await page.locator('#contact_no').fill('09171234567');
    await page.locator(`#document_${documentKey}`).check();
    await page.locator('#purpose').fill('Employment requirements');
    await page.getByRole('button', { name: 'Submit request' }).click();
    await page.getByRole('button', { name: 'Confirm and submit' }).click();

    const reference = page.locator('text=/REQ-[A-Z0-9]{8}/').first();
    await reference.waitFor();

    return (await reference.textContent()).match(/REQ-[A-Z0-9]{8}/)[0];
}
