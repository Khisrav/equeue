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

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Очереди';
    protected static ?string $label = 'Очередь';
    protected static ?string $pluralLabel = 'Очереди';
    protected static ?string $modelLabel = 'очередь';

    public static function form(Form $form): Form
    {
        //get users medical institution
        $currentMedicalInstitution = Auth::user()->medicalInstitution;
        
        Log::info(Auth::user());
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('patient_name')
                    ->label('Имя пациента')
                    ->required(),
                Forms\Components\TextInput::make('patient_phone')
                    ->label('Телефон пациента')
                    ->required(),
                Forms\Components\Select::make('patient_gender')
                    ->label('Пол пациента')
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('medical_institution_id')
                    ->label('Мед. учреждение')
                    ->options(MedicalInstitution::all()->pluck('name', 'id'))
                    ->default($currentMedicalInstitution->id)
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\Select::make('doctor_id')
                    ->label('Врач')
                    ->options(User::where('medical_institution_id', $currentMedicalInstitution->id)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(Queue::getStatuses())
                    ->default('waiting')
                    ->dehydrated()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->rows(3)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Имя пациента')
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_phone')
                    ->label('Телефон пациента')
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_gender')
                    ->label('Пол пациента')
                    ->searchable(),
                Tables\Columns\TextColumn::make('medical_institution_id')
                    ->label('Мед. учреждение')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor_id')
                    ->label('Врач')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Примечания')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Время начала')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Время окончания')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageQueues::route('/'),
        ];
    }
}
