<?php

namespace App\Filament\Resources\MedicalInstitutionResource\Pages;

use App\Filament\Resources\MedicalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMedicalInstitutions extends ManageRecords
{
    protected static string $resource = MedicalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
