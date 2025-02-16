<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobTitleResource\Pages;
use App\Filament\Resources\JobTitleResource\RelationManagers;
use App\Models\JobTitle;
use Filament\Forms;
use Filament\Forms\Form;
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
protected static ?string $navigationGroup = 'Recursos Humanos';
protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
        Forms\Components\Section::make('')
            ->columns(1)
                ->schema([

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Cargo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
//                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
                    Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                    Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
//                    Tables\Actions\ReplicateAction::make(),
                    Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Medium),
//                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListJobTitles::route('/'),
//            'create' => Pages\CreateJobTitle::route('/create'),
//            'edit' => Pages\EditJobTitle::route('/{record}/edit'),
        ];
    }
}
