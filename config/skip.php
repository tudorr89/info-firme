<?php

/*
|--------------------------------------------------------------------------
| Banned CIFs
|--------------------------------------------------------------------------
|
| Companies whose CUI is listed here are never imported. The ONRC publishes
| them, but they must not appear in this application, so they are skipped at
| import time and an existing row can be removed with
| `php artisan company:delete {cif}`.
|
| `$always` is enforced in code so a missing or incomplete environment cannot
| bring a banned company back; `SKIP_CIFS_IMPORT` adds to it.
|
*/

$always = [
    '52491237',
];

return [

    'always' => $always,

    'cifs' => array_values(array_unique(array_filter(array_merge(
        $always,
        array_map('trim', explode(',', (string) env('SKIP_CIFS_IMPORT', ''))),
    )))),

];
