<?php

namespace App\Filament\Resources\CifRemovalRequests\Pages;

use App\Filament\Resources\CifRemovalRequests\CifRemovalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCifRemovalRequests extends ListRecords
{
    protected static string $resource = CifRemovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        // Requests are only created through the public form, never by hand.
        return [];
    }
}
