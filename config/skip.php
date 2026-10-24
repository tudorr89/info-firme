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
    | Every request submitted through the public form is e-mailed here, so the
    | operator finds out without having to watch the admin panel.
    |
    */

    'notification_email' => env('CIF_REMOVAL_NOTIFICATION_EMAIL', 'tudorr89@gmail.com'),

];
