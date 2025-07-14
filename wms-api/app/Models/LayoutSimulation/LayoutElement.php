<?php

namespace App\Models\LayoutSimulation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayoutElement extends Model
{
    use HasFactory;

    protected $table = 'layout_elements';

    protected $fillable = [
        'layout_simulation_id',
        'element_type',
        'element_name',
        'position_x',
        'position_y',
        'width',
        'height',
        'rotation',
        'properties',
        'constraints',
        'is_movable',
        'is_resizable',
        'z_index'
    ];

    protected $casts = [
        'position_x' => 'decimal:2',
        'position_y' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'rotation' => 'decimal:2',
        'properties' => 'json',
        'constraints' => 'json',
        'is_movable' => 'boolean',
        'is_resizable' => 'boolean',
        'z_index' => 'integer'
    ];

    // Relationships
    public function layoutSimulation()
    {
        return $this->belongsTo(LayoutSimulation::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('element_type', $type);
    }

    public function scopeMovable($query)
    {
        return $query->where('is_movable', true);
    }

    public function scopeResizable($query)
    {
        return $query->where('is_resizable', true);
    }

    // Methods
    public function move($newX, $newY)
    {
        if (!$this->is_movable) {
            throw new \Exception('This element cannot be moved');
        }

        // Check constraints
        if ($this->violatesConstraints($newX, $newY, $this->width, $this->height)) {
            throw new \Exception('Move violates element constraints');
        }

        $this->position_x = $newX;
        $this->position_y = $newY;
        $this->save();
    }

    public function resize($newWidth, $newHeight)
    {
        if (!$this->is_resizable) {
            throw new \Exception('This element cannot be resized');
        }

        // Check constraints
        if ($this->violatesConstraints($this->position_x, $this->position_y, $newWidth, $newHeight)) {
            throw new \Exception('Resize violates element constraints');
        }

        $this->width = $newWidth;
        $this->height = $newHeight;
        $this->save();
    }

    public function rotate($degrees)
    {
        $this->rotation = $degrees % 360;
        $this->save();
    }

    public function getBounds()
    {
        return [
            'x1' => $this->position_x,
            'y1' => $this->position_y,
            'x2' => $this->position_x + $this->width,
            'y2' => $this->position_y + $this->height
        ];
    }

    public function overlaps(LayoutElement $other)
    {
        $thisBounds = $this->getBounds();
        $otherBounds = $other->getBounds();

        return !($thisBounds['x2'] <= $otherBounds['x1'] || 
                $thisBounds['x1'] >= $otherBounds['x2'] || 
                $thisBounds['y2'] <= $otherBounds['y1'] || 
                $thisBounds['y1'] >= $otherBounds['y2']);
    }

    public function getDistance(LayoutElement $other)
    {
        $thisCenter = $this->getCenter();
        $otherCenter = $other->getCenter();

        return sqrt(
            pow($thisCenter['x'] - $otherCenter['x'], 2) + 
            pow($thisCenter['y'] - $otherCenter['y'], 2)
        );
    }

    public function getCenter()
    {
        return [
            'x' => $this->position_x + ($this->width / 2),
            'y' => $this->position_y + ($this->height / 2)
        ];
    }

    public function getArea()
    {
        return $this->width * $this->height;
    }

    public function setProperty($key, $value)
    {
        $properties = $this->properties ?? [];
        $properties[$key] = $value;
        $this->properties = $properties;
        $this->save();
    }

    public function getProperty($key, $default = null)
    {
        $properties = $this->properties ?? [];
        return $properties[$key] ?? $default;
    }

    private function violatesConstraints($x, $y, $width, $height)
    {
        $constraints = $this->constraints ?? [];

        // Check minimum/maximum size constraints
        if (isset($constraints['min_width']) && $width < $constraints['min_width']) {
            return true;
        }
        if (isset($constraints['max_width']) && $width > $constraints['max_width']) {
            return true;
        }
        if (isset($constraints['min_height']) && $height < $constraints['min_height']) {
            return true;
        }
        if (isset($constraints['max_height']) && $height > $constraints['max_height']) {
            return true;
        }

        // Check position constraints
        if (isset($constraints['min_x']) && $x < $constraints['min_x']) {
            return true;
        }
        if (isset($constraints['max_x']) && ($x + $width) > $constraints['max_x']) {
            return true;
        }
        if (isset($constraints['min_y']) && $y < $constraints['min_y']) {
            return true;
        }
        if (isset($constraints['max_y']) && ($y + $height) > $constraints['max_y']) {
            return true;
        }

        // Check if element must stay within layout bounds
        if ($constraints['stay_within_bounds'] ?? false) {
            $layout = $this->layoutSimulation;
            $layoutData = $layout->layout_data;
            
            if ($x < 0 || $y < 0 || 
                ($x + $width) > $layoutData['dimensions']['width'] || 
                ($y + $height) > $layoutData['dimensions']['height']) {
                return true;
            }
        }

        return false;
    }

    public static function getElementTypes()
    {
        return [
            'storage_rack' => 'Storage Rack',
            'picking_station' => 'Picking Station',
            'packing_station' => 'Packing Station',
            'receiving_dock' => 'Receiving Dock',
            'shipping_dock' => 'Shipping Dock',
            'aisle' => 'Aisle',
            'workstation' => 'Workstation',
            'office' => 'Office',
            'equipment' => 'Equipment',
            'safety_zone' => 'Safety Zone',
            'column' => 'Structural Column',
            'wall' => 'Wall'
        ];
    }

    public function getDefaultProperties()
    {
        $defaults = [
            'storage_rack' => [
                'capacity' => 100,
                'levels' => 4,
                'accessibility' => 'both_sides'
            ],
            'picking_station' => [
                'capacity' => 50,
                'equipment' => 'handheld_scanner'
            ],
            'packing_station' => [
                'throughput' => 30,
                'equipment' => 'scale_printer'
            ],
            'receiving_dock' => [
                'dock_doors' => 1,
                'truck_capacity' => 'standard'
            ],
            'shipping_dock' => [
                'dock_doors' => 1,
                'truck_capacity' => 'standard'
            ]
        ];

        return $defaults[$this->element_type] ?? [];
    }
}