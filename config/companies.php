<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company statuses
    |--------------------------------------------------------------------------
    |
    | ONRC publishes the state of every company in `n_stare_firma.csv` and links
    | it from `od_stare_firma.csv`. Only 1048 (`funcțiune`) means the company is
    | currently operating; the other codes describe dissolution, liquidation,
    | insolvency, expiry of the registered office and similar situations.
    |
    */

    'active_statuses' => array_map(
        'intval',
        array_filter(explode(',', env('COMPANY_ACTIVE_STATUSES', '1048')))
    ),

    'obsolete_statuses' => array_map(
        'intval',
        array_filter(explode(',', env(
            'COMPANY_OBSOLETE_STATUSES',
            implode(',', [
                1049, // dizolvare
                1050, // reorganizare - fuziune prin absorbtie
                1052, // lichidare
                1054, // reorganizare - divizare partiala
                1055, // reorganizare - divizare totala
                1070, // faliment
                1073, // insolvabila
                1075, // inmatriculare respinsa
                1076, // dizolvare - capital sub limita impusa de lege
                1078, // declarare nulitate
                1085, // valabilitate sediu expirata
                1087, // capital neechilibrat
                1094, // dizolvare urmare a expirarii duratei de functionare
                1097, // transferat la ORC Ilfov
                1099, // renuntare la judecata
                1101, // denumire radiata ca urmare a schimbarii denumirii
                1105, // insolvabila (art.131 din O.G. nr. 61/2002)
                1109, // dizolvare de drept (art.30 din Legea nr. 359/2004)
                1111, // dizolvare de drept cu desfiintarea incheierii
                1113, // dizolvare judiciara (art.237 din Legea nr. 31/1990)
                1115, // firma mama in dizolvare
                1116, // inchiderea procedurii reorganizarii judiciare si a falimentului
                1118, // inchiderea procedurii reorganizarii judiciare si a falimentului
                1120, // dizolvare conform art.235 din Legea nr. 31/1990
                1122, // fuziune efectiva
                2067, // capital neechilibrat
                2069, // sediu expirat
                2076, // capital subscris sub limita legala
            ])
        )))
    ),

    /*
    |--------------------------------------------------------------------------
    | Hide obsolete companies
    |--------------------------------------------------------------------------
    |
    | When enabled, companies whose status is obsolete are excluded from search,
    | from the public API and from the sitemap. The company page stays reachable
    | by direct link so existing URLs keep working.
    |
    */

    'hide_obsolete' => (bool) env('HIDE_OBSOLETE_COMPANIES', true),

];
