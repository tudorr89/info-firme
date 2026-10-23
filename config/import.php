<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import Directory
    |--------------------------------------------------------------------------
    |
    | The directory (relative to the configured filesystem disk) that holds the
    | downloaded ONRC CSV files. Both the downloader and the import commands
    | read from this location.
    |
    */

    'disk' => env('IMPORT_DISK', 'local'),

    'directory' => env('IMPORT_DIRECTORY', 'imports'),

    /*
    |--------------------------------------------------------------------------
    | Import Map
    |--------------------------------------------------------------------------
    |
    | Maps the canonical CSV filename to the artisan command that imports it.
    | The order of this array is the order in which files are imported: the
    | company file must come first because every other import links to it.
    |
    */

    'files' => [
        'od_firme.csv' => 'import:companies',
        'n_stare_firma.csv' => 'import:nomenclator',
        'n_versiune_caen.csv' => 'import:caen-version',
        'n_caen.csv' => 'import:caen-definition',
        'od_stare_firma.csv' => 'import:status',
        'od_caen_autorizat.csv' => 'import:caen-company',
        'od_reprezentanti_legali.csv' => 'import:legal-representatives',
        'od_reprezentanti_if.csv' => 'import:natural-persons',
        'od_sucursale_alte_state_membre.csv' => 'import:eu-branches',
    ],

];
