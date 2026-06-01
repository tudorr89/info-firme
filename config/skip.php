<?php

return [
    'cifs' => array_filter(array_map('trim', explode(',', env('SKIP_CIFS_IMPORT', '')))),
];
