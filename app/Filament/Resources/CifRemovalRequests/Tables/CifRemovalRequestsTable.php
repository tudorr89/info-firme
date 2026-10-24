<?php

namespace App\Filament\Resources\CifRemovalRequests\Tables;

use App\Enums\CifRemovalStatus;
use App\Models\CifRemovalRequest;
use App\Services\CifRemovalService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CifRemovalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('cui')
                    ->label('CUI / CIF')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->limit(45)
                    ->placeholder('—'),
                TextColumn::make('requester_name')
                    ->label('Solicitant')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('requester_email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Stare')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Trimisă')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stare')
                    ->options(CifRemovalStatus::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobă')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobi ștergerea acestui CIF?')
                    ->modalDescription('CIF-ul va fi blocat definitiv: firma dispare din căutare, din paginile publice și din API, iar datele ei sunt șterse din baza de date.')
                    ->modalSubmitActionLabel('Da, aprobă și șterge')
                    ->schema([
                        Textarea::make('review_note')
                            ->label('Notă internă (opțional)')
                            ->rows(3),
                    ])
                    ->visible(fn (CifRemovalRequest $record): bool => $record->isPending())
                    ->action(function (CifRemovalRequest $record, array $data): void {
                        $deleted = CifRemovalService::approve($record, auth()->user(), $data['review_note'] ?? null);

                        Notification::make()
                            ->title('Cerere aprobată')
                            ->body(sprintf(
                                'CIF %s a fost blocat. Înregistrări șterse: %d firme, %d adrese, %d stări, %d coduri CAEN.',
                                $record->cui,
                                $deleted['companies'],
                                $deleted['addresses'],
                                $deleted['statuses'],
                                $deleted['caen_companies'],
                            ))
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Respinge')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Respingi cererea?')
                    ->modalDescription('Firma rămâne vizibilă. Poți lăsa o notă internă cu motivul respingerii.')
                    ->modalSubmitActionLabel('Da, respinge')
                    ->schema([
                        Textarea::make('review_note')
                            ->label('Motivul respingerii')
                            ->rows(3)
                            ->required(),
                    ])
                    ->visible(fn (CifRemovalRequest $record): bool => $record->isPending())
                    ->action(function (CifRemovalRequest $record, array $data): void {
                        CifRemovalService::reject($record, auth()->user(), $data['review_note'] ?? null);

                        Notification::make()
                            ->title('Cerere respinsă')
                            ->body("CIF {$record->cui} rămâne vizibil.")
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->label('Detalii')
                    ->icon(Heroicon::OutlinedEye),

                // Only unresolved requests may be removed (spam, duplicates).
                // A reviewed one is a decision record: rejecting it is the way
                // to reverse it, so that the un-ban is auditable.
                DeleteAction::make()
                    ->label('Șterge')
                    ->icon(Heroicon::OutlinedTrash)
                    ->modalHeading('Ștergi cererea?')
                    ->modalDescription('Se poate șterge doar o cerere nesoluționată. Firmele nu sunt afectate.')
                    ->visible(fn (CifRemovalRequest $record): bool => $record->isPending()),
            ])
            ->toolbarActions([]);
    }
}
