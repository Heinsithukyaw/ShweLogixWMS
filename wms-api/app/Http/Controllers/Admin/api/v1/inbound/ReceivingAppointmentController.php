<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\ReceivingAppointment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingAppointmentResource;
use App\Repositories\Admin\api\v1\inbound\ReceivingAppointmentRepository;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingAppointmentCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreReceivingAppointmentRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateReceivingAppointmentRequest;

class ReceivingAppointmentController extends Controller
{
    protected $receivingAppointmentRepository;

    public function __construct(ReceivingAppointmentRepository $receivingAppointmentRepository)
    {
        $this->receivingAppointmentRepository = $receivingAppointmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ReceivingAppointmentCollection
    {
        $receiving_appointments = ReceivingAppointment::with(['inbound_shipment','supplier','dock'])->get();
        return new ReceivingAppointmentCollection($receiving_appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceivingAppointmentRequest $request): ReceivingAppointmentResource
    {
        $receiving_appointment = $this->receivingAppointmentRepository->create($request->validated());
        return new ReceivingAppointmentResource($receiving_appointment);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(ReceivingAppointment $receiving_appointment): ReceivingAppointmentResource
    {
        $receiving_appointment = $this->receivingAppointmentRepository->get($receiving_appointment);
        return new ReceivingAppointmentResource($receiving_appointment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceivingAppointmentRequest $request, ReceivingAppointment $receivingAppointment): ReceivingAppointmentResource
    {
        $update_receiving_appointment = $this->receivingAppointmentRepository->update($receivingAppointment, $request->validated());
        return new ReceivingAppointmentResource($update_receiving_appointment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivingAppointment $delete_receiving_appointment)
    {
        $this->receivingAppointmentRepository->delete($delete_receiving_appointment);
        return response()->json([
            'status' => true,
            'message' => 'Receiving Appointment deleted successfully.',
        ]);
    }
}
