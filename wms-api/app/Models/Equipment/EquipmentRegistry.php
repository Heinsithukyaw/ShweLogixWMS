<?php

namespace App\Models\Equipment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Employee;

class EquipmentRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_code',
        'name',
        'description',
        'category_id',
        'warehouse_id',
        'current_location_id',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
        'purchase_cost',
        'current_value',
        'status',
        'condition',
        'specifications',
        'attachments',
        'notes',
        'is_mobile',
        'requires_operator',
        'assigned_operator'
    ];

    protected $casts = [
        'specifications' => 'array',
        'attachments' => 'array',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'is_mobile' => 'boolean',
        'requires_operator' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_operator');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceSchedule::class, 'equipment_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceRecord::class, 'equipment_id');
    }

    public function utilizationTracking(): HasMany
    {
        return $this->hasMany(EquipmentUtilizationTracking::class, 'equipment_id');
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(EquipmentPerformanceMetric::class, 'equipment_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class, 'equipment_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(EquipmentAlert::class, 'equipment_id');
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(EquipmentLifecycleEvent::class, 'equipment_id');
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(EquipmentSparePart::class, 'equipment_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUnderMaintenance(): bool
    {
        return in_array($this->status, ['maintenance', 'repair']);
    }

    public function isWarrantyValid(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry > now();
    }

    public function getDepreciationRateAttribute()
    {
        if (!$this->purchase_date || $this->purchase_cost <= 0) {
            return 0;
        }
        
        $ageInYears = $this->purchase_date->diffInYears(now());
        return $ageInYears > 0 ? (($this->purchase_cost - $this->current_value) / $this->purchase_cost) * 100 : 0;
    }

    public function getAgeInYearsAttribute()
    {
        return $this->purchase_date ? $this->purchase_date->diffInYears(now()) : 0;
    }

    public function hasActiveAlerts(): bool
    {
        return $this->alerts()->where('status', 'active')->exists();
    }

    public function getNextMaintenanceDueAttribute()
    {
        return $this->maintenanceSchedules()
            ->where('is_active', true)
            ->orderBy('next_due_date')
            ->first()?->next_due_date;
    }
}