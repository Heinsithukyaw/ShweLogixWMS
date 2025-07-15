<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\ReceivingDock;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingDockResource;
use App\Repositories\Admin\api\v1\inbound\ReceivingDockRepository;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingDockCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreReceivingDockRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateReceivingDockRequest;

class ReceivingDockController extends Controller
{
    protected $receivingDockRepository;

    public function __construct(ReceivingDockRepository $receivingDockRepository)
    {
        $this->receivingDockRepository = $receivingDockRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ReceivingDockCollection
    {
        $receiving_docks = ReceivingDock::with(['zone'])->get();
        return new ReceivingDockCollection($receiving_docks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceivingDockRequest $request): ReceivingDockResource
    {
        $validated = $request->validated();
        $validated['features'] = json_encode($validated['features']);
        $receiving_dock = $this->receivingDockRepository->create($validated);
        return new ReceivingDockResource($receiving_dock);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(ReceivingDock $receiving_dock): ReceivingDockResource
    {
        $receiving_dock = $this->receivingDockRepository->get($receiving_dock);
        return new ReceivingDockResource($receiving_dock);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceivingDockRequest $request, ReceivingDock $receivingDock): ReceivingDockResource
    {
        $validated = $request->validated();
        $validated['features'] = json_encode($validated['features']);
        $update_receiving_dock = $this->receivingDockRepository->update($receivingDock, $validated);
        return new ReceivingDockResource($update_receiving_dock);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivingDock $receivingDock)
    {
        $this->receivingDockRepository->delete($receivingDock);
        return response()->json([
            'status' => true,
            'message' => 'Receiving Dock deleted successfully.',
        ]);
    }
}
