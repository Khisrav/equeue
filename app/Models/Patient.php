<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'city', 'gender', 'date_of_birth', 'blood_type', 'medical_institution_id'];

    public function medicalInstitution()
    {
        return $this->belongsTo(MedicalInstitution::class);
    }
}
