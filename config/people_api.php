<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer "My People" API - token enforcement
    |--------------------------------------------------------------------------
    |
    | GET/POST/DELETE /api/people identify the customer by the Sanctum token
    | issued when an OTP is verified, taken from the Authorization header.
    |
    | This defaults to ON: an email address alone is not proof of identity, and
    | while this was off anyone who knew an address could read, edit or delete
    | that account's people. Set PEOPLE_API_REQUIRE_TOKEN=false in .env only as a
    | temporary rollback if a deployed frontend is not sending a token yet.
    |
    */

    'require_token' => env('PEOPLE_API_REQUIRE_TOKEN', true),

];
