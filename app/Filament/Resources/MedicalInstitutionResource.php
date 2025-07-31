<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalInstitutionResource\Pages;
use App\Filament\Resources\MedicalInstitutionResource\RelationManagers;
use App\Models\MedicalInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MedicalInstitutionResource extends Resource
{
    protected static ?string $model = MedicalInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Мед. учреждения';
    protected static ?string $label = 'Мед. учреждениe';
    protected static ?string $pluralLabel = 'Мед. учреждения';
    protected static ?string $modelLabel = 'мед. учреждение';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название мед. учреждения')
                    ->prefixIcon('heroicon-o-building-office')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label('Адрес')
                    ->prefixIcon('heroicon-o-map-pin')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    // ->tel()
                    ->prefixIcon('heroicon-o-phone')
                    ->prefix('+992')
                    ->placeholder('99 (999) 99-99')
                    ->mask('99 (999) 99-99')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->prefixIcon('heroicon-o-envelope')
                    ->email()
                    ->placeholder('example@example.com')
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->label('Сайт')
                    ->prefixIcon('heroicon-o-globe-alt')
                    ->placeholder('https://example.com')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->label('Логотип')
                    // ->required()
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                    ])
                    ->directory('medical-institutions')
                    ->default('no-logo.png')
                    ->imageResizeMode('cover'),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    // ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Лого')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('/storage/medical-institutions/no-logo.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->wrap()
                    ->searchable()
                    ->description(fn (MedicalInstitution $record) => $record->description)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->wrap()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('website')
                    ->label('Сайт')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMedicalInstitutions::route('/'),
        ];
    }
}
