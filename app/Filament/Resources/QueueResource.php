<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QueueResource\Pages;
use App\Filament\Resources\QueueResource\RelationManagers;
use App\Models\MedicalInstitution;
use App\Models\Queue;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\FontWeight;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Очереди';
    protected static ?string $label = 'Очередь';
    protected static ?string $pluralLabel = 'Очереди';
    protected static ?string $modelLabel = 'очередь';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $currentMedicalInstitution = Auth::user()->medicalInstitution;
        
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о пациенте')
                    ->description('Основные данные пациента')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('patient_name')
                                    ->label('Имя пациента')
                                    ->prefixIcon('heroicon-o-user')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Введите полное имя'),
                                
                                Forms\Components\TextInput::make('patient_phone')
                                    ->label('Телефон пациента')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->tel()
                                    ->prefix('+992')
                                    ->placeholder('XX XXX XX XX')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Select::make('patient_gender')
                            ->label('Пол пациента')
                            ->prefixIcon('heroicon-o-identification')
                            ->options([
                                'male' => 'Мужской',
                                'female' => 'Женский',
                            ])
                            ->native(false)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Информация о приеме')
                    ->description('Детали медицинского приема')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('medical_institution_id')
                                    ->label('Мед. учреждение')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->options(MedicalInstitution::all()->pluck('name', 'id'))
                                    ->default($currentMedicalInstitution->id)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                
                                Forms\Components\Select::make('doctor_id')
                                    ->label('Врач')
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->options(User::where('medical_institution_id', $currentMedicalInstitution->id)
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ]),
                        
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->prefixIcon('heroicon-o-signal')
                            ->options(Queue::getStatuses())
                            ->default('waiting')
                            ->dehydrated()
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Примечания')
                            ->placeholder('Дополнительная информация о приеме...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Номер талона')
                    ->badge()
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->size('lg')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Пациент')
                    ->description(fn (Queue $record): string => $record->patient_phone ?: 'Телефон не указан')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('patient_gender')
                    ->label('Пол')
                    ->icon(fn (string $state): string => match ($state) {
                        'male' => 'heroicon-o-user',
                        'female' => 'heroicon-o-user',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'pink',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Врач')
                    ->description(fn (Queue $record): string => 
                        $record->doctor?->specialization . ' • Каб. ' . $record->doctor?->room_number
                    )
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'called' => 'info',
                        'skipped' => 'danger',
                        'done' => 'success',
                        'canceled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Queue::getStatuses()[$state] ?? $state),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('notes')
                    ->label('Примечания')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(Queue::getStatuses())
                    ->placeholder('Все статусы'),
                
                SelectFilter::make('doctor_id')
                    ->label('Врач')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('patient_gender')
                    ->label('Пол пациента')
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('call_patient')
                        ->label('Вызвать')
                        ->icon('heroicon-o-megaphone')
                        ->color('info')
                        ->visible(fn (Queue $record) => $record->status === 'waiting')
                        ->action(function (Queue $record) {
                            $record->update(['status' => 'called']);
                        }),
                    
                    Tables\Actions\Action::make('mark_done')
                        ->label('Завершить')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Queue $record) => in_array($record->status, ['waiting', 'called']))
                        ->action(function (Queue $record) {
                            $record->update([
                                'status' => 'done',
                                'end_time' => now()
                            ]);
                        }),
                    
                    Tables\Actions\Action::make('mark_skipped')
                        ->label('Пропустить')
                        ->icon('heroicon-o-forward')
                        ->color('warning')
                        ->visible(fn (Queue $record) => in_array($record->status, ['waiting', 'called']))
                        ->action(function (Queue $record) {
                            $record->update(['status' => 'skipped']);
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
                    
                    Tables\Actions\BulkAction::make('mark_called')
                        ->label('Отметить как вызванные')
                        ->icon('heroicon-o-megaphone')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'called']);
                        }),
                ]),
            ])
            ->emptyStateHeading('Нет записей в очереди')
            ->emptyStateDescription('Создайте первую запись в очереди')
            ->emptyStateIcon('heroicon-o-queue-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQueues::route('/'),
        ];
    }
}
