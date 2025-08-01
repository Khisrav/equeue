<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\MedicalInstitution;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $label = 'Пользователь';
    protected static ?string $pluralLabel = 'Пользователи';
    protected static ?string $modelLabel = 'пользователь';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Базовые данные пользователя')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Полное имя')
                                    ->prefixIcon('heroicon-o-user')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Введите полное имя'),
                                
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('user@example.com'),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Пароль')
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->password()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->maxLength(255)
                                    ->placeholder('Введите пароль'),
                                
                                Forms\Components\DateTimePicker::make('email_verified_at')
                                    ->label('Email подтвержден')
                                    ->prefixIcon('heroicon-o-check-badge')
                                    ->displayFormat('d.m.Y H:i')
                                    ->placeholder('Дата подтверждения email'),
                            ]),
                    ]),

                Forms\Components\Section::make('Профессиональная информация')
                    ->description('Медицинская специализация и рабочие данные')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('medical_institution_id')
                                    ->label('Мед. учреждение')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->relationship('medicalInstitution', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                        Forms\Components\TextInput::make('address')
                                            ->required(),
                                    ])
                                    ->native(false),
                                
                                Forms\Components\TextInput::make('specialization')
                                    ->label('Специализация')
                                    ->prefixIcon('heroicon-o-academic-cap')
                                    ->maxLength(255)
                                    ->placeholder('Например: Кардиолог, Терапевт'),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('room_number')
                                    ->label('Номер кабинета')
                                    ->prefixIcon('heroicon-o-home')
                                    ->numeric()
                                    ->placeholder('101'),
                                
                                Forms\Components\FileUpload::make('avatar')
                                    ->label('Аватар')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['1:1'])
                                    ->directory('avatars')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->placeholder('Загрузите фото профиля'),
                            ]),
                    ]),

                Forms\Components\Section::make('Роли и права доступа')
                    ->description('Системные права пользователя')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Роли')
                            ->prefixIcon('heroicon-o-user-group')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->helperText('Выберите одну или несколько ролей для пользователя'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Фото')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF')
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('medicalInstitution.name')
                    ->label('Мед. учреждение')
                    ->description(fn (User $record): string => 
                        $record->specialization ? 'Специализация: ' . $record->specialization : 'Специализация не указана'
                    )
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('room_number')
                    ->label('Кабинет')
                    ->badge()
                    ->color('info')
                    ->prefix('№ ')
                    ->placeholder('Не указан')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->placeholder('Нет ролей'),
                
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email')
                    ->icon(fn ($state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->tooltip(fn ($state): string => $state ? 'Email подтвержден' : 'Email не подтвержден'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('medical_institution_id')
                    ->label('Мед. учреждение')
                    ->relationship('medicalInstitution', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('roles')
                    ->label('Роль')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email подтвержден')
                    ->placeholder('Все пользователи')
                    ->trueLabel('Подтвержден')
                    ->falseLabel('Не подтвержден')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Просмотр'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Редактировать'),
                    
                    Tables\Actions\Action::make('verify_email')
                        ->label('Подтвердить email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (User $record) => !$record->email_verified_at)
                        ->action(function (User $record) {
                            $record->update(['email_verified_at' => now()]);
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('Удалить'),
                ])
                ->label('Действия')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('verify_emails')
                        ->label('Подтвердить email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['email_verified_at' => now()]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Нет пользователей')
            ->emptyStateDescription('Создайте первого пользователя системы')
            ->emptyStateIcon('heroicon-o-users');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
