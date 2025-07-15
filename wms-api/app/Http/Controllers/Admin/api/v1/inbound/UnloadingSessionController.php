<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\UnloadingSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\UnloadingSessionResource;
use App\Repositories\Admin\api\v1\inbound\UnloadingSessionRepository;
use App\Http\Resources\Admin\api\v1\inbound\UnloadingSessionCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreUnloadingSessionRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateUnloadingSessionRequest;

class UnloadingSessionController extends Controller
{
    protected $unloadingSessionRepository;

    public function __construct(UnloadingSessionRepository $unloadingSessionRepository)
    {
        $this->unloadingSessionRepository = $unloadingSessionRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): UnloadingSessionCollection
    {
        $unloading_sessions = UnloadingSession::with(['inbound_shipment','employee','dock'])->get();
        return new UnloadingSessionCollection($unloading_sessions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnloadingSessionRequest $request): UnloadingSessionResource
    {
        $validated = $request->validated();
        $validated['equipment_used'] = json_encode($validated['equipment_used']);
        $unloading_session = $this->unloadingSessionRepository->create($validated);
        return new UnloadingSessionResource($unloading_session);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(UnloadingSession $unloading_session): UnloadingSessionResource
    {
        $unloading_session = $this->unloadingSessionRepository->get($unloading_session);
        return new UnloadingSessionResource($unloading_session);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnloadingSessionRequest $request, UnloadingSession $unloadingSession): UnloadingSessionResource
    {
        $validated = $request->validated();
        $validated['equipment_used'] = json_encode($validated['equipment_used']);
        $update_unloading_session = $this->unloadingSessionRepository->update($unloadingSession, $validated);
        return new UnloadingSessionResource($update_unloading_session);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnloadingSession $unloadingSession)
    {
        $this->unloadingSessionRepository->delete($unloadingSession);
        return response()->json([
            'status' => true,
            'message' => 'Unloading Session deleted successfully.',
        ]);
    }
}
