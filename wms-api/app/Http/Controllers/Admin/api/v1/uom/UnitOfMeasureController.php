<?php

namespace App\Http\Controllers\Admin\api\v1\uom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\api\v1\uom\StoreUnitOfMeasureRequest;
use App\Repositories\Admin\api\v1\uom\UnitOfMeasureRepository;
use App\Http\Requests\Admin\api\v1\uom\UpdateUnitOfMeasureRequest;
use App\Http\Resources\Admin\api\v1\uom\UnitOfMeasureCollection;
use App\Http\Resources\Admin\api\v1\uom\UnitOfMeasureResource;
use Illuminate\Http\Response;
use App\Models\BaseUom;
use App\Models\UnitOfMeasure;

class UnitOfMeasureController extends Controller
{
    protected $unitOfMeasureRepository;

    public function __construct(UnitOfMeasureRepository $unitOfMeasureRepository)
    {
        $this->unitOfMeasureRepository = $unitOfMeasureRepository;
    }

    public function getBaseUomLists(){
        $base_uoms = BaseUom::all();
        return response()->json(['status' => 200, 'message' => 'Retrieve Base UOM Lists', 'data' => $base_uoms], 200);
    }

    public function index(): UnitOfMeasureCollection
    {
        $unit_of_measures = $this->unitOfMeasureRepository->getAll();
        return new UnitOfMeasureCollection($unit_of_measures);
    }

    public function store(StoreUnitOfMeasureRequest $request): UnitOfMeasureResource
    {
        $unit_of_measure = $this->unitOfMeasureRepository->create($request->validated());
        return new UnitOfMeasureResource($unit_of_measure);
    }

    public function show(UnitOfMeasure $unit_of_measure): UnitOfMeasureResource
    {
        $unit_of_measure = $this->unitOfMeasureRepository->get($unit_of_measure);
        return new UnitOfMeasureResource($unit_of_measure->load('posts'));
    }

    public function update(UpdateUnitOfMeasureRequest $request, UnitOfMeasure $unit_of_measure): UnitOfMeasureResource
    {
        $unit_of_measure = $this->unitOfMeasureRepository->update($unit_of_measure, $request->validated());
        return new UnitOfMeasureResource($unit_of_measure);
    }

    public function destroy(UnitOfMeasure $unit_of_measure)
    {
        logger($unit_of_measure);
        $this->unitOfMeasureRepository->delete($unit_of_measure);
        return response()->json([
            'status' => true,
            'message' => 'Unit of Measure deleted successfully.',
        ]);
    }
}
