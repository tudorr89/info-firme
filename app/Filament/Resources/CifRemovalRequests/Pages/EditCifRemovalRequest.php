<?php

namespace App\Filament\Resources\CifRemovalRequests\Pages;

use App\Filament\Resources\CifRemovalRequests\CifRemovalRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCifRemovalRequest extends EditRecord
{
    protected static string $resource = CifRemovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
