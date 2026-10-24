<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Banned CIFs enforced in code
    |--------------------------------------------------------------------------
    |
    | Companies whose CUI is listed here are never imported and never
    | published. This list is enforced in code so a missing or incomplete
    | database cannot bring a banned company back.
    |
    | Removal requests are handled in the application, not here: an approved
    | `cif_removal_requests` row bans a CIF in exactly the same way, and is
    | managed from the admin panel. `php artisan company:delete {cif}` removes
    | the data of a CIF that is already in the database.
    |
    */

    'always' => [
        '52491237',
    ],

    /*
    |--------------------------------------------------------------------------
    | Where removal requests are announced
    |--------------------------------------------------------------------------
    |
    | A new request is pushed to Gotify so the operator finds out without having
    | to watch the admin panel. Only production sets GOTIFY_ENABLED (the values
    | live in .env.production); everywhere else this stays off.
    |
    */

    'gotify' => [
        'enabled' => (bool) env('GOTIFY_ENABLED', false),
        'url' => env('GOTIFY_URL', ''),
        'token' => env('GOTIFY_TOKEN', ''),
    ],

];
