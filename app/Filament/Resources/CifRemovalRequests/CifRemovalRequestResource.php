<?php

namespace App\Filament\Resources\CifRemovalRequests;

use App\Filament\Resources\CifRemovalRequests\Pages\EditCifRemovalRequest;
use App\Filament\Resources\CifRemovalRequests\Pages\ListCifRemovalRequests;
use App\Filament\Resources\CifRemovalRequests\Schemas\CifRemovalRequestForm;
use App\Filament\Resources\CifRemovalRequests\Tables\CifRemovalRequestsTable;
use App\Models\CifRemovalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CifRemovalRequestResource extends Resource
{
    protected static ?string $model = CifRemovalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $modelLabel = 'cerere de ștergere';

    protected static ?string $pluralModelLabel = 'Cereri de ștergere';

    protected static ?string $navigationLabel = 'Cereri de ștergere';

    protected static ?string $recordTitleAttribute = 'cui';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        $pending = CifRemovalRequest::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return CifRemovalRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CifRemovalRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCifRemovalRequests::route('/'),
            'edit' => EditCifRemovalRequest::route('/{record}/edit'),
        ];
    }
}
