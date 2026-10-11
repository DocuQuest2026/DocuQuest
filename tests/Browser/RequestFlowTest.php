<?php

use Laravel\Dusk\Browser;

test('a student submits a request and then finds it on the status page with its queue place', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')->driver->manage()->deleteAllCookies();

        $browser->visit('/request')
            ->type('student_no', '2026-00077')
            ->type('first_name', 'Ana')
            ->type('last_name', 'Reyes')
            ->select('course', 'BS Information Technology')
            ->type('email', 'dusk.student@gmail.com')
            ->type('contact_no', '09171234567')
            ->check('#document_certificate_of_enrolment')
            ->type('purpose', 'Scholarship application')
            ->press('Submit request')
            ->waitForText('Review your request')
            ->press('Confirm and submit')
            ->waitForText('REQ-');

        $reference = $browser->script('return document.body.innerText.match(/REQ-[A-Z0-9]{8}/)[0];')[0];

        $browser->visit('/request/status')
            ->type('reference_no', $reference)
            ->press('Check status')
            ->waitForText('Being processed')
            ->assertSee('Place in queue')
            ->assertSee('Estimated ready by');
    });
});
