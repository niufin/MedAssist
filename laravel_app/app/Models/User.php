<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mrn',
        'password',
        'role',
        'status',
        'medical_center_name',
        'degrees',
        'designation',
        'additional_qualifications',
        'license_number',
        'contact_number',
        'hospital_admin_id',
        'age',
        'gender',
    ];

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_HOSPITAL_ADMIN = 'hospital_admin';
    const ROLE_DOCTOR = 'doctor';
    const ROLE_PHARMACIST = 'pharmacist';
    const ROLE_LAB_ASSISTANT = 'lab_assistant';
    const ROLE_PATIENT = 'patient';

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isHospitalAdmin()
    {
        return $this->role === self::ROLE_HOSPITAL_ADMIN;
    }

    public function isDoctor()
    {
        return $this->role === self::ROLE_DOCTOR;
    }

    public function isPharmacist()
    {
        return $this->role === self::ROLE_PHARMACIST;
    }

    public function isLabAssistant()
    {
        return $this->role === self::ROLE_LAB_ASSISTANT;
    }

    public function isPatient()
    {
        return $this->role === self::ROLE_PATIENT;
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function hospitalUsers()
    {
        return $this->hasMany(self::class, 'hospital_admin_id');
    }

    public function hospitalAdmin()
    {
        return $this->belongsTo(self::class, 'hospital_admin_id');
    }

    public function assignedPatients()
    {
        return $this->belongsToMany(self::class, 'doctor_patient_access', 'doctor_id', 'patient_id')
            ->withPivot(['hospital_admin_id'])
            ->withTimestamps();
    }

    public function assignedDoctors()
    {
        return $this->belongsToMany(self::class, 'doctor_patient_access', 'patient_id', 'doctor_id')
            ->withPivot(['hospital_admin_id'])
            ->withTimestamps();
    }
}
