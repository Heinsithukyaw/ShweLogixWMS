<?php

namespace App\Http\Controllers\Admin\api\v1\geographical;

use App\Models\State;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\geographical\StateResource;
use App\Repositories\Admin\api\v1\geographical\StateRepository;
use App\Http\Resources\Admin\api\v1\geographical\StateCollection;
use App\Http\Requests\Admin\api\v1\geographical\StoreStateRequest;
use App\Http\Requests\Admin\api\v1\geographical\UpdateStateRequest;

class StateController extends Controller
{
    protected $stateRepository;

    public function __construct(StateRepository $stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): StateCollection
    {
        $states = State::with(['country'])->get();
        return new StateCollection($states);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStateRequest $request): StateResource
    {
        $state = $this->stateRepository->create($request->validated());
        return new StateResource($state);
    }

    /**
     * Display the specified resource.
     */
    public function show(State $state): StateResource
    {
        $state = $this->stateRepository->get($state);
        return new StateResource($state->load('country'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStateRequest $request, State $state): StateResource
    {
        $update_state = $this->stateRepository->update($state, $request->validated());
        return new StateResource($update_state);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(State $delete_state)
    {
        $this->stateRepository->delete($delete_state);
        return response()->json([
            'status' => true,
            'message' => 'State deleted successfully.',
        ]);
    }
}
