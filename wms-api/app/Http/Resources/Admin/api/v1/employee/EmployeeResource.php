<?php

namespace App\Http\Resources\Admin\api\v1\employee;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'employees.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'employee_name' => $this->employee_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'address' => $this->address,
            'department_id' => $this->department_id,
            'job_title' => $this->job_title,
            'employment_type' => $this->employment_type,
            'shift' => $this->shift,
            'hire_date' => $this->hire_date,
            'salary' => $this->salary,
            'currency' => $this->currency,
            'is_supervisor' => (int)$this->is_supervisor,
            'status' => $this->status
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
