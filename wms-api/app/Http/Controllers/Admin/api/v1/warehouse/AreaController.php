<?php

namespace App\Http\Controllers\Admin\api\v1\warehouse;

use App\Models\Area;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\warehouse\AreaResource;
use App\Repositories\Admin\api\v1\warehouse\AreaRepository;
use App\Http\Resources\Admin\api\v1\warehouse\AreaCollection;
use App\Http\Requests\Admin\api\v1\warehouse\StoreAreaRequest;
use App\Http\Requests\Admin\api\v1\warehouse\UpdateAreaRequest;

class AreaController extends Controller
{
    protected $areaRepository;

    public function __construct(AreaRepository $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AreaCollection
    {
        // $categories = $this->categoryRepository->getAll();
        $areas = Area::with(['warehouse'])->get();
        return new AreaCollection($areas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAreaRequest $request): AreaResource
    {
        $area = $this->areaRepository->create($request->validated());
        return new AreaResource($area);
    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area): AreaResource
    {
        $area = $this->areaRepository->get($area);
        return new AreaResource($area->load('warehouse'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAreaRequest $request, Area $area): AreaResource
    {
        $area = $this->areaRepository->update($area, $request->validated());
        return new AreaResource($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        $this->areaRepository->delete($area);
        return response()->json([
            'status' => true,
            'message' => 'Area deleted successfully.',
        ]);
    }
}
