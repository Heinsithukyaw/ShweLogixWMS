<?php

namespace App\Http\Controllers\Admin\api\v1\employee;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\employee\EmployeeResource;
use App\Repositories\Admin\api\v1\employee\EmployeeRepository;
use App\Http\Resources\Admin\api\v1\employee\EmployeeCollection;
use App\Http\Requests\Admin\api\v1\employee\StoreEmployeeRequest;
use App\Http\Requests\Admin\api\v1\employee\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
    protected $employeeRepository;

    public function __construct(employeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): EmployeeCollection
    {
        $employees = Employee::all();
        return new EmployeeCollection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): EmployeeResource
    {
        $employee = $this->employeeRepository->create($request->validated());
        return new EmployeeResource($employee);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): EmployeeResource
    {
        $employee = $this->employeeRepository->get($employee);
        return new employeeResource($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $emp = $this->employeeRepository->update($employee, $request->validated());
        return new EmployeeResource($emp);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $emp)
    {
        $this->employeeRepository->delete($emp);
        return response()->json([
            'status' => true,
            'message' => 'Employee deleted successfully.',
        ]);
    }
}
