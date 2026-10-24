<?php

namespace App\Filament\Resources\CifRemovalRequests\Schemas;

use App\Models\CifRemovalRequest;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CifRemovalRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cererea')
                    ->schema([
                        TextEntry::make('cui')
                            ->label('CUI / CIF'),
                        TextEntry::make('company_name')
                            ->label('Firma')
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Stare')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Trimisă la')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Solicitant')
                    ->schema([
                        TextEntry::make('requester_name')
                            ->label('Nume'),
                        TextEntry::make('requester_email')
                            ->label('E-mail')
                            ->copyable(),
                        TextEntry::make('ip_address')
                            ->label('Adresa IP')
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Motivul invocat')
                    ->schema([
                        TextEntry::make('reason')
                            ->label('')
                            ->prose(),
                    ]),

                Section::make('Soluționare')
                    ->schema([
                        TextEntry::make('reviewed_at')
                            ->label('Soluționată la')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('reviewer.name')
                            ->label('Soluționată de')
                            ->placeholder('—'),
                        Textarea::make('review_note')
                            ->label('Notă internă')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (?CifRemovalRequest $record): bool => $record !== null && ! $record->isPending()),
            ]);
    }
}
