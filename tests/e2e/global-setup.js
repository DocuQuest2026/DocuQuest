import { execSync } from 'node:child_process';

/**
 * Rebuild the isolated "e2e" schema from scratch and add the test accounts, so every run
 * starts from the same known state.
 */
export default async function globalSetup() {
    const run = (command) => execSync(command, { stdio: 'inherit', env: { ...process.env, APP_ENV: 'e2e' } });

    run('php artisan migrate:fresh --force --env=e2e');
    run('php artisan db:seed --class=E2eSeeder --force --env=e2e');
}
