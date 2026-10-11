<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Laravel\Dusk\TestCase as BaseTestCase;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    private const URL = 'http://127.0.0.1:8002';

    private static ?Process $server = null;

    private static bool $prepared = false;

    /**
     * Start ChromeDriver and the isolated app once, before the first browser test. This runs from
     * setUp() rather than a BeforeClass hook because Pest does not call that hook.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Dusk resets the base URL to APP_URL (the real dev site) before every test, so always point
        // it back at the isolated copy.
        Browser::$baseUrl = self::URL;

        if (self::$prepared) {
            return;
        }

        self::$prepared = true;

        // Started here rather than with startChromeDriver() because Dusk stops its own copy after the
        // first test file, which would leave every later file without a browser.
        $chromeDriver = (new ChromeProcess)->toProcess(['--port=9515']);
        $chromeDriver->start();
        register_shutdown_function(fn () => $chromeDriver->stop());

        static::startIsolatedApp();
    }

    /**
     * Serve a separate copy of the app that uses its own "dusk" database schema (see
     * .env.dusk.local), so the browser tests never touch real data or the phpunit "test" schema.
     */
    private static function startIsolatedApp(): void
    {
        if (self::$server !== null) {
            return;
        }

        // Everything phpunit.xml forces into this process must not leak into the app being served.
        $env = array_fill_keys([
            'APP_MAINTENANCE_DRIVER', 'BCRYPT_ROUNDS', 'BROADCAST_CONNECTION', 'CACHE_STORE', 'DB_CONNECTION',
            'DB_SCHEMA', 'DB_URL', 'MAIL_MAILER', 'QUEUE_CONNECTION', 'SESSION_DRIVER', 'PULSE_ENABLED',
            'TELESCOPE_ENABLED', 'NIGHTWATCH_ENABLED',
        ], false);
        $env['APP_ENV'] = 'dusk.local';

        (new Process(['php', 'artisan', 'migrate:fresh', '--force'], base_path(), $env))->setTimeout(300)->mustRun();
        (new Process(['php', 'artisan', 'db:seed', '--class=E2eSeeder', '--force'], base_path(), $env))->setTimeout(120)->mustRun();

        self::$server = new Process(
            [PHP_BINARY, '-d', 'zend_extension=opcache', '-d', 'opcache.enable_cli=1', '-S', '127.0.0.1:8002', '-t', '.', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            public_path(),
            $env,
        );
        self::$server->start();

        register_shutdown_function(fn () => self::$server?->stop());

        for ($attempt = 0; $attempt < 50; $attempt++) {
            if ((self::URL.'/') !== false) {
                break;
            }

            usleep(200_000);
        }

        Browser::$baseUrl = self::URL;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://127.0.0.1:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
