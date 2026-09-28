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

    'cancellation_window_days' => 3,

];
