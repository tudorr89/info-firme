<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CSVDownloadService
{
    public static function download($url): void
    {
        $months = [
          1 =>  'ianuarie',
          4 =>  'aprilie',
          7 =>  'iulie',
          10 => 'octombrie'
        ];

        $now = Carbon::now();

        if(!in_array($now->month, array_keys($months))) {
            return;
        }

        if(count(Storage::files('csv')) > 0) {
            return;
        }

        $request = HTTP::get($url);

        $info = $request->json();

        if($info['success'] == false) {
            return;
        }

        foreach($info['result']['resources'] as $resource) {
            if($resource['format'] == 'CSV') {
                $csv = HTTP::timeout(900)->get($resource['url']);
                $csv = $csv->body();
                Storage::put('csv/'.basename($resource['url']), $csv);
            }
        }

        return;
    }
}
