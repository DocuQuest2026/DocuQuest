import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8001';

/**
 * End-to-end browser tests. They run against their own copy of the app (the "e2e" database
 * schema, see .env.e2e), never against real data, so they are safe to run any time.
 */
export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chrome',
            use: { ...devices['Desktop Chrome'], channel: 'chrome' },
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'], channel: 'chrome' },
            testMatch: /public-pages\.spec\.js/,
        },
    ],
    webServer: {
        command: 'php -d zend_extension=opcache -d opcache.enable_cli=1 -S 127.0.0.1:8001 -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
        cwd: 'public',
        // A static file: the database is only built by globalSetup, after the server is up.
        url: `${baseURL}/robots.txt`,
        // A fresh cache prefix per run, so nothing cached by an earlier run (or by the dev site) is reused.
        env: { APP_ENV: 'e2e', CACHE_PREFIX: `e2e_${Date.now()}_` },
        reuseExistingServer: false,
        timeout: 60_000,
    },
});
