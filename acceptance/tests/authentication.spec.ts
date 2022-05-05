import {test, expect} from '@playwright/test';

test.describe('authentication', () => {

    test('login / logout / login again', async ({page, baseURL}) => {
        const performLogin = async () => {
            await page.goto('/neos');
            const title = page.locator('.neos-login-heading');
            await expect(title).toHaveText('Login to Neos Demo Site');

            await page.locator('#username').fill('admin');
            await page.locator('#password').fill('password');

            await page.locator('button[type="submit"]:visible').click();

            await expect(page.locator('h1')).not.toContainText('Got exception:');
            await expect(page).toHaveURL(/neos\/content/);
        };

        await test.step('homepage', async () => {
            await page.goto('/');
            const title = page.locator('.container h1');
            await expect(title).toBeVisible();
        });

        await test.step('first login', performLogin);

        await test.step('logout', async () => {
            const userDropdown = page.locator('[role="button"]', { hasText: 'John Snow' });
            await expect(userDropdown).toBeVisible();
            await userDropdown.click();

            const logoutButton = page.locator('button', { hasText: 'Logout' });
            await expect(logoutButton).toBeVisible();
            await logoutButton.click();

            await expect(page.locator('h1')).not.toContainText('Got exception:');
        });

        await test.step('second login', performLogin);
    });

})
