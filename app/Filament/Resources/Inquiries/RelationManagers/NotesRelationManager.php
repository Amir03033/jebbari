<?php

namespace App\Filament\Resources\Inquiries\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Internal notes, kept as a history with author and time. */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Interne notities';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Notitie')->required()->rows(4)->maxLength(5000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')->label('Notitie')->wrap(),
                TextColumn::make('user.name')->label('Door')->placeholder('—'),
                TextColumn::make('created_at')->label('Wanneer')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()->label('Notitie toevoegen')->mutateDataUsing(fn (array $data) => [...$data, 'user_id' => auth()->id()]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
