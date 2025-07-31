<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalInstitution extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email', 'website', 'logo', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
