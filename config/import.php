<?php

use App\Jobs\CaenCompanyImportJob;
use App\Jobs\CaenDefinitionImportJob;
use App\Jobs\CaenVersionImportJob;
use App\Jobs\CompanyImportJob;
use App\Jobs\EUBranchImportJob;
use App\Jobs\LegalRepresentativeImportJob;
use App\Jobs\NaturalPersonRepresentativeImportJob;
use App\Jobs\NomenclatorImportJob;
use App\Jobs\StatusImportJob;

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
    | Maps the canonical CSV filename to the job that imports it. The order of
    | this array is the order in which the files are dispatched: the company file
    | comes first because every other import links to it.
    |
    */

    'files' => [
        'od_firme.csv' => CompanyImportJob::class,
        'n_stare_firma.csv' => NomenclatorImportJob::class,
        'n_versiune_caen.csv' => CaenVersionImportJob::class,
        'n_caen.csv' => CaenDefinitionImportJob::class,
        'od_stare_firma.csv' => StatusImportJob::class,
        'od_caen_autorizat.csv' => CaenCompanyImportJob::class,
        'od_reprezentanti_legali.csv' => LegalRepresentativeImportJob::class,
        'od_reprezentanti_if.csv' => NaturalPersonRepresentativeImportJob::class,
        'od_sucursale_alte_state_membre.csv' => EUBranchImportJob::class,
    ],

];
