<?php

namespace App\Models\Visualization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseFloorPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'total_length',
        'total_width',
        'total_height',
        'scale_unit',
        'layout_data',
        'image_path',
        'grid_settings',
        'is_active',
        'description'
    ];

    protected $casts = [
        'total_length' => 'decimal:2',
        'total_width' => 'decimal:2',
        'total_height' => 'decimal:2',
        'layout_data' => 'array',
        'grid_settings' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function elements()
    {
        return $this->hasMany(FloorPlanElement::class, 'floor_plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    // Methods
    public function getTotalArea()
    {
        return $this->total_length * $this->total_width;
    }

    public function getTotalVolume()
    {
        return $this->total_length * $this->total_width * $this->total_height;
    }

    public function getScaleRatio()
    {
        return $this->grid_settings['scale_ratio'] ?? 1;
    }

    public function convertToPixels($realWorldDistance)
    {
        return $realWorldDistance * $this->getScaleRatio();
    }

    public function convertToRealWorld($pixelDistance)
    {
        return $pixelDistance / $this->getScaleRatio();
    }

    public function addElement($elementData)
    {
        return $this->elements()->create($elementData);
    }

    public function removeElement($elementId)
    {
        return $this->elements()->where('id', $elementId)->delete();
    }

    public function updateElement($elementId, $elementData)
    {
        return $this->elements()->where('id', $elementId)->update($elementData);
    }

    public function getElementsByType($elementType)
    {
        return $this->elements()->where('element_type', $elementType)->get();
    }

    public function getVisibleElements()
    {
        return $this->elements()->where('is_visible', true)->orderBy('z_index')->get();
    }

    public function exportLayout()
    {
        return [
            'floor_plan' => $this->toArray(),
            'elements' => $this->getVisibleElements()->toArray()
        ];
    }

    public function importLayout($layoutData)
    {
        // Update floor plan data
        $this->update($layoutData['floor_plan']);
        
        // Clear existing elements
        $this->elements()->delete();
        
        // Import new elements
        foreach ($layoutData['elements'] as $elementData) {
            $this->addElement($elementData);
        }
    }

    public function createNewVersion()
    {
        $newVersion = $this->replicate();
        $newVersion->version = $this->getNextVersion();
        $newVersion->is_active = false;
        $newVersion->save();
        
        // Copy elements
        foreach ($this->elements as $element) {
            $newElement = $element->replicate();
            $newElement->floor_plan_id = $newVersion->id;
            $newElement->save();
        }
        
        return $newVersion;
    }

    private function getNextVersion()
    {
        $currentVersion = floatval($this->version);
        return number_format($currentVersion + 0.1, 1);
    }
}