<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Courses
    |--------------------------------------------------------------------------
    |
    | The courses students can pick from when requesting a record. Add, remove
    | or rename entries here to match the programs your school offers.
    |
    */

    'courses' => [
        'BS Information Technology',
        'BS Computer Science',
        'BS Business Administration',
        'BS Education',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Verification
    |--------------------------------------------------------------------------
    |
    | How many years a released document's verification link stays valid for.
    | Printed documents are expected to be presented as proof long after they
    | are released, so this is much longer than the request cancellation window.
    |
    */

    'document_verification_ttl_years' => 5,

    /*
    |--------------------------------------------------------------------------
    | Cancellation Window
    |--------------------------------------------------------------------------
    |
    | How many days after submitting a request the requester is allowed to ask
    | for it to be cancelled. Past this window, they must contact the
    | registrar directly.
    |
    */

    'cancellation_window_days' => 1,

    /*
    |--------------------------------------------------------------------------
    | Processing Time
    |--------------------------------------------------------------------------
    |
    | The most days the registrar needs to process a request, depending on the
    | document requested. This is only shown to requesters as guidance.
    |
    */

    'processing_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | First Request Month
    |--------------------------------------------------------------------------
    |
    | The first month (as YYYY-MM) the system was in use. The month filter on the
    | staff requests list and the date filters on the reports page can not go
    | earlier than this, since no requests exist before it.
    |
    */

    'first_request_month' => '2026-09',

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Scale
    |--------------------------------------------------------------------------
    |
    | Multiplies the attempt limits on the public forms. Leave it at 1; only the
    | automated load tests, which all come from one address, raise it.
    |
    */

    'rate_limit_scale' => (int) env('RATE_LIMIT_SCALE', 1),

    /*
    |--------------------------------------------------------------------------
    | Daily Request Limit
    |--------------------------------------------------------------------------
    |
    | The most document requests the registrar accepts in a single day, across
    | all requesters. Once it is reached, the request form is closed until the
    | next day.
    |
    */

    'daily_request_limit' => (int) env('DAILY_REQUEST_LIMIT', 20),

];
