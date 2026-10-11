<?php

use Laravel\Dusk\Browser;

test('the landing page offers every document and a request button', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSourceHas('Request Now')
            ->assertSee('Transcript of Records')
            ->assertSee('₱150.00 per copy');
    });
});

test('choosing a document on the request form shows the fee and the estimated ready date', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/request')
            ->assertSee('No document chosen yet.')
            ->check('#document_transcript_of_records')
            ->waitForText('Estimated ready by')
            ->assertSee('₱150.00');
    });
});

test('the status page rejects an unknown reference number', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/request/status')
            ->type('reference_no', 'REQ-NOTREAL1')
            ->press('Check status')
            ->waitForText('No request was found for that reference number.');
    });
});
