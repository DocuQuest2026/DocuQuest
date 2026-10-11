<?php

use Laravel\Dusk\Browser;

/**
 * Browsers are reused between tests, so start each one signed out.
 */
function signedOutStaffLogin(Browser $browser): Browser
{
    $browser->visit('/')->driver->manage()->deleteAllCookies();

    $browser->visit('/staff/login')
        ->type('email', 'e2e.staff@gmail.com')
        ->type('password', 'password');

    return $browser->press('Log in')->waitForLocation('/dashboard');
}

test('staff can sign in, open the request list and the reports page', function () {
    $this->browse(function (Browser $browser) {
        signedOutStaffLogin($browser)
            ->visit('/requests')
            ->assertSourceHas('>Queue<')
            ->visit('/reports')
            ->assertSourceHas('Download PDF');
    });
});

test('staff are refused the admin Documents page', function () {
    $this->browse(function (Browser $browser) {
        signedOutStaffLogin($browser)
            ->visit('/admin/document-types')
            ->assertSee('403');
    });
});
