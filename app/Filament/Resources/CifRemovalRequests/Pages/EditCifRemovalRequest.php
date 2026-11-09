<?php

namespace App\Filament\Resources\CifRemovalRequests\Pages;

use App\Filament\Resources\CifRemovalRequests\CifRemovalRequestResource;
use App\Models\CifRemovalRequest;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCifRemovalRequest extends EditRecord
{
    protected static string $resource = CifRemovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A reviewed request is a decision record, so it must not be
            // deletable. The model refuses it as well, but hiding the action
            // keeps the page from blowing up with a DomainException.
            DeleteAction::make()
                ->visible(fn (CifRemovalRequest $record): bool => $record->isPending()),
        ];
    }
}
