<?php

namespace App\Http\Controllers\Admin\api\v1\outbound;

use App\Models\PickWave;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\outbound\PickWaveResource;
use App\Repositories\Admin\api\v1\outbound\PickWaveRepository;
use App\Http\Resources\Admin\api\v1\outbound\PickWaveCollection;
use App\Http\Requests\Admin\api\v1\outbound\StorePickWaveRequest;
use App\Http\Requests\Admin\api\v1\outbound\UpdatePickWaveRequest;

class PickWaveController extends Controller
{
    protected $pickWaveRepository;

    public function __construct(PickWaveRepository $pickWaveRepository)
    {
        $this->pickWaveRepository = $pickWaveRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): PickWaveCollection
    {
        $pick_waves = PickWave::with(['assignedEmployee'])->orderBy('id', 'desc')->get();
        return new PickWaveCollection($pick_waves);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePickWaveRequest $request): PickWaveResource
    {
        $pickWave = $this->pickWaveRepository->create($request->validated());
        return new PickWaveResource($pickWave);
    }

    /**
     * Display the specified resource.
     */
    public function show(PickWave $pickWave): PickWaveResource
    {
        $pickWave = $this->pickWaveRepository->get($pickWave);
        return new PickWaveResource($pickWave);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePickWaveRequest $request, PickWave $pickWave): PickWaveResource
    {
        $pickWave = $this->pickWaveRepository->update($pickWave, $request->validated());
        return new PickWaveResource($pickWave);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PickWave $pickWave)
    {
        $this->pickWaveRepository->delete($pickWave);
        return response()->json([
            'status' => true,
            'message' => 'Pick Wave deleted successfully.',
        ]);
    }
} 