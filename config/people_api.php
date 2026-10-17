<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer "My People" API - token enforcement
    |--------------------------------------------------------------------------
    |
    | GET/POST/DELETE /api/people identify the customer either by the Sanctum
    | token issued when an OTP is verified (preferred, taken from the
    | Authorization header) or - until every deployed frontend sends one - by
    | the `customer_email` query/body parameter, which anyone who knows an
    | email address could use to read, edit or delete that account's people.
    |
    | Flip this to true once the customer frontend is deployed with token
    | support: the email-only fallback stops working and requests without a
    | token are answered with 401.
    |
    */

    'require_token' => env('PEOPLE_API_REQUIRE_TOKEN', false),

];
