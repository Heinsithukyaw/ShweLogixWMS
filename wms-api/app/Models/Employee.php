<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'employee_name',
        'email',
        'phone_number',
        'dob',
        'gender',
        'nationality',
        'address',
        'department_id',
        'job_title',
        'employment_type',
        'shift',
        'hire_date',
        'salary',
        'currency',
        'is_supervisor',
        'status'
    ];


}
