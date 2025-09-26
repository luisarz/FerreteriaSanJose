<?php

namespace App\Filament\Resources\JobTitles;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\JobTitles\Pages\ListJobTitles;
use App\Filament\Resources\JobTitleResource\Pages;
use App\Filament\Resources\JobTitleResource\RelationManagers;
use App\Models\JobTitle;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JobTitleResource extends Resource
{
    protected static ?string $model = JobTitle::class;

protected static ?string $label = 'Cargos laborales';
protected static string | \UnitEnum | null $navigationGroup = 'Recursos Humanos';
protected static ?int $navigationSort = 1;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
        Section::make('')
            ->columns(1)
                ->schema([

                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Cargo')
                            ->required()
                            ->maxLength(255),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Cargo')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make([
                    ViewAction::make()->label('')->iconSize(IconSize::Medium),
                    EditAction::make()->label('')->iconSize(IconSize::Medium),
                    DeleteAction::make()->label('')->iconSize(IconSize::Medium),
//                    Tables\Actions\ReplicateAction::make(),
                    RestoreAction::make()->label('')->iconSize(IconSize::Medium),
//                    ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListJobTitles::route('/'),
//            'create' => Pages\CreateJobTitle::route('/create'),
//            'edit' => Pages\EditJobTitle::route('/{record}/edit'),
        ];
    }
}
